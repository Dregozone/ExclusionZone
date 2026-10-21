<?php

use App\Actions\Game\FeedBrainScorcher;
use App\ItemRarity;
use App\Livewire\Admin\ManageItems;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Lottery;
use Livewire\Livewire;

function scorcherPlayerIn(string $cityName): User
{
    $user = User::factory()->create();
    $city = City::query()->where('city', $cityName)->firstOrFail();

    $user->location()->update([
        'country_id' => $city->country_id,
        'city_id' => $city->id,
    ]);

    return $user;
}

function giveScorcherOffering(User $user, string $itemKey, int $quantity): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);

    return $item;
}

function carriedQuantity(User $user, Item $item): int
{
    return (int) $user->fresh()->inventoryItems()->where('item_id', $item->id)->value('quantity');
}

/**
 * @return array<int, ItemRarity>
 */
function carriedRaritiesOtherThan(User $user, Item $item): array
{
    return $user->fresh()->inventoryItems()->with('item')->get()
        ->reject(fn ($inventoryItem): bool => $inventoryItem->item_id === $item->id)
        ->map(fn ($inventoryItem): ItemRarity => $inventoryItem->item->rarity)
        ->values()
        ->all();
}

function feedAndCompleteScorcher(User $user, Item $item, int $quantity): string
{
    test()->actingAs($user)
        ->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => $quantity])
        ->assertRedirect(route('dashboard'));

    Carbon::setTestNow(now()->addHour());

    return test()->actingAs($user)
        ->post(route('work.complete'))
        ->assertRedirect(route('dashboard'))
        ->getSession()
        ->get('status');
}

afterEach(function () {
    Carbon::setTestNow();
});

// ── Rarities ─────────────────────────────────────────────────────────────────

test('existing items get a rarity from the best loot tier of the actions that drop them', function () {
    $raritiesByKey = Item::query()
        ->whereIn('key', ['clean_water', 'drone_parts', 'metal_parts', 'rare_components'])
        ->pluck('rarity', 'key');

    expect($raritiesByKey->all())->toEqual([
        'clean_water' => ItemRarity::Common,
        'drone_parts' => ItemRarity::Uncommon,
        'metal_parts' => ItemRarity::Rare,
        'rare_components' => ItemRarity::Epic,
    ]);
});

test('scorched items exist but no city action drops them', function () {
    $scorchedKeys = Item::query()->where('rarity', ItemRarity::Scorched)->pluck('key');
    $droppedKeys = CityAction::query()->pluck('reward_profile')->pluck('item_key');

    expect($scorchedKeys)->toHaveCount(4)
        ->and($scorchedKeys->intersect($droppedKeys))->toBeEmpty();
});

// ── Odds ─────────────────────────────────────────────────────────────────────

test('odds always add up to one hundred percent and favour rarer, bigger offerings', function () {
    $feedBrainScorcher = new FeedBrainScorcher;

    $singleCommon = $feedBrainScorcher->oddsFor(ItemRarity::Common, 1);
    $manyCommon = $feedBrainScorcher->oddsFor(ItemRarity::Common, 16);
    $singleEpic = $feedBrainScorcher->oddsFor(ItemRarity::Epic, 1);

    expect($singleCommon)->toBe(['jackpot' => 2, 'upgrade' => 12, 'same' => 20, 'downgrade' => 0, 'nothing' => 66])
        ->and($manyCommon)->toBe(['jackpot' => 6, 'upgrade' => 22, 'same' => 20, 'downgrade' => 0, 'nothing' => 52])
        ->and($singleEpic)->toBe(['jackpot' => 5, 'upgrade' => 20, 'same' => 20, 'downgrade' => 20, 'nothing' => 35])
        ->and($feedBrainScorcher->oddsFor(ItemRarity::Common, 500))->toBe($manyCommon);
});

// ── Starting an offering ─────────────────────────────────────────────────────

test('the brain scorcher can only be fed in pripyat', function () {
    $user = scorcherPlayerIn('Kyiv');
    $item = giveScorcherOffering($user, 'scrap_metal', 3);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => 1])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['text'] === 'The Brain Scorcher is only found in Pripyat.');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('players cannot offer more than they carry', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 2);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => 3])
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['text'] === 'You need 3x Scrap Metal to make that offering.');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('an offering needs a quantity of at least one', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 2);

    $this->actingAs($user)
        ->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => 0])
        ->assertSessionHasErrors('quantity');
});

test('feeding the brain scorcher starts timed work and keeps the offering until it completes', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 5);

    $this->actingAs($user)
        ->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => 3])
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('user_works', [
        'user_id' => $user->id,
        'work_type' => 'brain_scorcher',
        'offered_item_id' => $item->id,
        'offered_quantity' => 3,
        'duration_seconds' => FeedBrainScorcher::BASE_DURATION_SECONDS,
    ]);
    expect(carriedQuantity($user, $item))->toBe(5);

    $this->actingAs($user)
        ->post(route('city-action.store'), ['city_action_id' => CityAction::query()->where('action_key', 'radiation_sampling')->value('id')])
        ->assertSessionHas('toast');
});

test('cancelling an offering gives the items back untouched', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 3);

    $this->actingAs($user)->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => 3]);

    $this->actingAs($user)
        ->post(route('work.cancel'))
        ->assertSessionHas('status', 'Offering withdrawn. Your items are still in your pack.');

    expect(carriedQuantity($user, $item))->toBe(3);
});

// ── Outcomes ─────────────────────────────────────────────────────────────────

test('a failed roll burns the offering for nothing', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 5);
    Lottery::alwaysLose();

    $status = feedAndCompleteScorcher($user, $item, 3);

    expect($status)->toBe('The Brain Scorcher burned 3x Scrap Metal and gave nothing back.')
        ->and(carriedQuantity($user, $item))->toBe(2)
        ->and(carriedRaritiesOtherThan($user, $item))->toBe([]);
});

test('the same roll hands back one of the offered item', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 3);
    Lottery::fix([false, false, true]);

    $status = feedAndCompleteScorcher($user, $item, 3);

    expect($status)->toBe('The Brain Scorcher burned 3x Scrap Metal and returned 1x Scrap Metal.')
        ->and(carriedQuantity($user, $item))->toBe(1);
});

test('an upgrade roll hands back an item one rarity higher', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 1);
    Lottery::fix([false, true]);

    $status = feedAndCompleteScorcher($user, $item, 1);

    expect($status)->toContain('returned something better')
        ->and(carriedQuantity($user, $item))->toBe(0)
        ->and(carriedRaritiesOtherThan($user, $item))->toBe([ItemRarity::Uncommon]);
});

test('a downgrade roll hands back an item one rarity lower', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'drone_parts', 1);
    Lottery::fix([false, false, false, true]);

    $status = feedAndCompleteScorcher($user, $item, 1);

    expect($status)->toContain('returned something worse')
        ->and(carriedRaritiesOtherThan($user, $item))->toBe([ItemRarity::Common]);
});

test('a jackpot roll hands back a scorched item', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 1);
    Lottery::alwaysWin();

    $status = feedAndCompleteScorcher($user, $item, 1);

    expect($status)->toStartWith('Jackpot! The Brain Scorcher burned 1x Scrap Metal')
        ->and(carriedRaritiesOtherThan($user, $item))->toBe([ItemRarity::Scorched]);
});

test('upgrading an epic item counts as a jackpot', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'rare_components', 1);
    Lottery::fix([false, true]);

    $status = feedAndCompleteScorcher($user, $item, 1);

    expect($status)->toStartWith('Jackpot!')
        ->and(carriedRaritiesOtherThan($user, $item))->toBe([ItemRarity::Scorched]);
});

test('nothing is burned when the offering is gone by the time the work completes', function () {
    $user = scorcherPlayerIn('Pripyat');
    $item = giveScorcherOffering($user, 'scrap_metal', 3);

    $this->actingAs($user)->post(route('brain-scorcher.store'), ['item_id' => $item->id, 'quantity' => 3]);
    $user->inventoryItems()->where('item_id', $item->id)->update(['quantity' => 2]);
    Carbon::setTestNow(now()->addHour());
    Lottery::alwaysWin();

    $this->actingAs($user)
        ->post(route('work.complete'))
        ->assertSessionHas('status', 'The Brain Scorcher found nothing to burn: you no longer have 3x Scrap Metal. Nothing was lost.');

    expect(carriedQuantity($user, $item))->toBe(2)
        ->and(carriedRaritiesOtherThan($user, $item))->toBe([]);
});

// ── City menu and admin ──────────────────────────────────────────────────────

test('the brain scorcher only appears on the city menu in pripyat', function () {
    $pripyatPlayer = scorcherPlayerIn('Pripyat');
    giveScorcherOffering($pripyatPlayer, 'scrap_metal', 2);
    $kyivPlayer = scorcherPlayerIn('Kyiv');

    $this->actingAs($pripyatPlayer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('The Brain Scorcher')
        ->assertSee('Scrap Metal (Common) · 2 carried');

    $this->actingAs($kyivPlayer)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('The Brain Scorcher');
});

test('admins can set an item rarity', function () {
    $item = Item::query()->where('key', 'scrap_metal')->firstOrFail();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageItems::class)
        ->call('edit', $item->id)
        ->assertSet('rarity', 'common')
        ->set('rarity', 'rare')
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->rarity)->toBe(ItemRarity::Rare);
});
