<?php

use App\Livewire\Admin\ManageLocations;
use App\Models\City;
use App\Models\Item;
use App\Models\User;
use App\Models\UserWork;
use Livewire\Livewire;

/**
 * A survivor at the Warsaw trade post with fixed bazaar rates: sales pay half the base price,
 * +2% per Barter level above 1, and +0.5% per extra item sold together; purchases cost 150%
 * of the base price, -1% per Barter level above 1.
 */
function shopper(array $attributes = [], string $cityName = 'Warsaw', int $barterLevel = 1): User
{
    config([
        'shop.sell_ratio_percent' => 50,
        'shop.barter_sell_bonus_percent_per_level' => 2,
        'shop.maximum_barter_sell_bonus_percent' => 50,
        'shop.bulk_bonus_tenths_percent_per_item' => 5,
        'shop.maximum_bulk_bonus_percent' => 10,
        'shop.buy_markup_percent' => 150,
        'shop.barter_buy_discount_percent_per_level' => 1,
        'shop.maximum_barter_buy_discount_percent' => 25,
        'shop.scrip_per_barter_xp' => 10,
    ]);

    $user = User::factory()->create($attributes);
    $city = City::query()->where('city', $cityName)->firstOrFail();
    $user->location()->update(['country_id' => $city->country_id, 'city_id' => $city->id]);
    $user->skillFor('barter')->update(['level' => $barterLevel]);

    return $user->fresh();
}

function pricedItem(string $key, int $basePrice): Item
{
    $item = Item::query()->where('key', $key)->firstOrFail();
    $item->update(['base_price' => $basePrice]);

    return $item->fresh();
}

function stockPack(User $user, Item $item, int $quantity): void
{
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);
}

function packed(User $user, Item $item): int
{
    return (int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity');
}

// ── Selling ──────────────────────────────────────────────────────────────────

test('selling to the bazaar pays half the base price and trains barter', function () {
    $user = shopper(['scrip' => 0]);
    $scrap = pricedItem('scrap_metal', 10);
    stockPack($user, $scrap, 3);

    $this->actingAs($user)
        ->postJson(route('bazaar.sell'), ['item_id' => $scrap->id, 'quantity' => 1])
        ->assertOk()
        ->assertJson(['status' => 'You sold 1x Scrap Metal for 5 scrip (+1 Barter XP).']);

    expect($user->fresh()->scrip)->toBe(5)
        ->and(packed($user, $scrap))->toBe(2)
        ->and($user->fresh()->skillFor('barter')->xp)->toBe(1);
});

test('barter skill and bigger stacks raise the sale price', function (int $barterLevel, int $quantity, int $expectedScrip) {
    $user = shopper(['scrip' => 0], barterLevel: $barterLevel);
    $scrap = pricedItem('scrap_metal', 100);
    stockPack($user, $scrap, $quantity);

    $this->actingAs($user)->postJson(route('bazaar.sell'), ['item_id' => $scrap->id, 'quantity' => $quantity])->assertOk();

    expect($user->fresh()->scrip)->toBe($expectedScrip)
        ->and(packed($user, $scrap))->toBe(0);
})->with([
    'level 1, one item' => [1, 1, 50],
    'level 11 adds 20%' => [11, 1, 60],
    'barter bonus stops at 50%' => [60, 1, 75],
    'ten items add 4.5%' => [1, 10, 522],
    'bulk bonus stops at 10%' => [1, 40, 2200],
]);

test('selling is refused without changing anything', function (string $itemKey, int $carried, int $quantity, string $message) {
    $user = shopper(['scrip' => 0]);
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    stockPack($user, $item, $carried);

    $this->actingAs($user)
        ->postJson(route('bazaar.sell'), ['item_id' => $item->id, 'quantity' => $quantity])
        ->assertUnprocessable()
        ->assertJson(['message' => $message]);

    expect($user->fresh()->scrip)->toBe(0)
        ->and(packed($user, $item))->toBe($carried);
})->with([
    'more than carried' => ['scrap_metal', 2, 3, 'You are not carrying 3 Scrap Metal.'],
    'a quest item' => ['dawnwatch_document', 1, 1, 'The bazaar won\'t take DAWNWATCH Document.'],
]);

// ── Buying ───────────────────────────────────────────────────────────────────

test('buying from the bazaar costs the marked up price less the barter discount', function (int $barterLevel, int $expectedCost) {
    $user = shopper(['scrip' => 500], barterLevel: $barterLevel);
    $boots = pricedItem('wellington_boots', 20);

    $this->actingAs($user)
        ->postJson(route('bazaar.buy'), ['item_id' => $boots->id, 'quantity' => 2])
        ->assertOk()
        ->assertJson(['status' => "You bought 2x Wellington Boots for {$expectedCost} scrip (+".(int) ceil($expectedCost / 10).' Barter XP).']);

    expect($user->fresh()->scrip)->toBe(500 - $expectedCost)
        ->and(packed($user, $boots))->toBe(2);
})->with([
    'level 1' => [1, 60],
    'level 11 saves 10%' => [11, 54],
    'discount stops at 25%' => [80, 46],
]);

test('buying is refused without changing anything', function (string $itemKey, int $scrip, string $message) {
    $user = shopper(['scrip' => $scrip]);
    $item = pricedItem($itemKey, 20);

    $this->actingAs($user)
        ->postJson(route('bazaar.buy'), ['item_id' => $item->id, 'quantity' => 1])
        ->assertUnprocessable()
        ->assertJson(['message' => $message]);

    expect($user->fresh()->scrip)->toBe($scrip)
        ->and(packed($user, $item))->toBe(0);
})->with([
    'not enough scrip' => ['gas_mask', 29, 'You need 30 scrip for that and have 29.'],
    'not in stock' => ['scrap_metal', 500, 'The bazaar doesn\'t sell Scrap Metal.'],
]);

test('the bazaar never pays more for an item than it charges', function () {
    $user = shopper(['scrip' => 10000], barterLevel: 100);
    $mask = pricedItem('gas_mask', 30);
    stockPack($user, $mask, 50);

    $this->actingAs($user)->postJson(route('bazaar.sell'), ['item_id' => $mask->id, 'quantity' => 50])->assertOk();
    $earned = $user->fresh()->scrip - 10000;
    $this->actingAs($user)->postJson(route('bazaar.buy'), ['item_id' => $mask->id, 'quantity' => 50])->assertOk();
    $spent = 10000 + $earned - $user->fresh()->scrip;

    expect($earned)->toBeLessThan($spent);
});

// ── Where and when ───────────────────────────────────────────────────────────

test('the bazaar is closed away from trade posts, on the road and when held by trouble', function (Closure $setUp, string $message) {
    $user = shopper(['scrip' => 100]);
    $boots = pricedItem('wellington_boots', 20);
    $setUp($user);

    $this->actingAs($user)
        ->postJson(route('bazaar.buy'), ['item_id' => $boots->id, 'quantity' => 1])
        ->assertUnprocessable()
        ->assertJson(['message' => $message]);

    expect($user->fresh()->scrip)->toBe(100);
})->with([
    'no trade post' => [function (User $user): void {
        $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();
        $user->location()->update(['country_id' => $kyiv->country_id, 'city_id' => $kyiv->id]);
    }, 'There is no bazaar here. Find a trade post.'],
    'travelling' => [function (User $user): void {
        UserWork::query()->create([
            'user_id' => $user->id,
            'work_type' => 'travel',
            'origin_city_id' => $user->location->city_id,
            'destination_city_id' => City::query()->where('city', 'Kyiv')->value('id'),
            'duration_seconds' => 60,
            'available_at' => now()->addMinute(),
        ]);
    }, 'You are on the road. Wait until you arrive.'],
]);

test('bazaar requests need a positive quantity', function () {
    $this->actingAs(shopper())
        ->postJson(route('bazaar.sell'), ['item_id' => Item::query()->value('id'), 'quantity' => 0])
        ->assertJsonValidationErrors('quantity');
});

test('guests are sent to log in before trading at a bazaar', function () {
    $this->post(route('bazaar.buy'), ['item_id' => 1, 'quantity' => 1])->assertRedirect(route('login'));
});

test('the city menu shows scrip and the bazaar at a trade post', function () {
    $user = shopper(['scrip' => 42]);
    stockPack($user, pricedItem('scrap_metal', 10), 2);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="hud-scrip"', false)
        ->assertSeeText('42')
        ->assertSee('data-test="buy-gas_mask"', false)
        ->assertSee('data-test="sell-scrap_metal"', false)
        ->assertDontSee('data-test="bazaar-restriction"', false);
});

test('new survivors start with some scrip', function () {
    expect(User::factory()->create()->fresh()->scrip)->toBe(25);
});

// ── Stock per city ───────────────────────────────────────────────────────────

test('a trade post with its own stock sells only that stock', function () {
    $user = shopper(['scrip' => 500]);
    $scrap = pricedItem('scrap_metal', 10);
    $boots = pricedItem('wellington_boots', 20);
    $user->location->city->bazaarItems()->attach($scrap);

    $this->actingAs($user)
        ->postJson(route('bazaar.buy'), ['item_id' => $scrap->id, 'quantity' => 2])
        ->assertOk();
    $this->actingAs($user)
        ->postJson(route('bazaar.buy'), ['item_id' => $boots->id, 'quantity' => 1])
        ->assertUnprocessable()
        ->assertJson(['message' => 'The bazaar doesn\'t sell Wellington Boots.']);

    expect(packed($user, $scrap))->toBe(2)
        ->and(packed($user, $boots))->toBe(0)
        ->and($user->fresh()->scrip)->toBe(470);
});

test('the city menu lists the trade post\'s own stock instead of the default', function () {
    $user = shopper();
    $user->location->city->bazaarItems()->attach(pricedItem('scrap_metal', 10));

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="buy-scrap_metal"', false)
        ->assertDontSee('data-test="buy-gas_mask"', false);
});

test('admins choose what a trade post\'s bazaar sells', function () {
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $scrap = pricedItem('scrap_metal', 10);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageLocations::class)
        ->call('edit', $warsaw->id)
        ->set('bazaarItemIds', [$scrap->id])
        ->call('save')
        ->assertHasNoErrors();

    expect($warsaw->bazaarItems()->pluck('items.id')->all())->toBe([$scrap->id]);
});

test('admins cannot stock an item without a base price', function () {
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $unpriced = pricedItem('scrap_metal', 0);

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageLocations::class)
        ->call('edit', $warsaw->id)
        ->set('bazaarItemIds', [$unpriced->id])
        ->call('save')
        ->assertHasErrors(['bazaarItemIds.0' => 'exists']);

    expect($warsaw->bazaarItems()->exists())->toBeFalse();
});

test('closing a trade post clears its bazaar stock', function () {
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $warsaw->bazaarItems()->attach(pricedItem('scrap_metal', 10));

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageLocations::class)
        ->call('edit', $warsaw->id)
        ->set('has_trade_post', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($warsaw->bazaarItems()->exists())->toBeFalse();
});
