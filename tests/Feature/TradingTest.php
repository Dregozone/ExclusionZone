<?php

use App\Events\TradeUpdated;
use App\Livewire\Admin\ManageItems;
use App\Livewire\Admin\ManageLocations;
use App\Livewire\TradeBoard;
use App\Models\City;
use App\Models\Item;
use App\Models\Trade;
use App\Models\User;
use App\TradeStatus;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

function traderIn(string $cityName, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    moveTraderTo($user, $cityName);

    return $user;
}

function moveTraderTo(User $user, string $cityName): void
{
    $city = City::query()->where('city', $cityName)->firstOrFail();

    $user->location()->update([
        'country_id' => $city->country_id,
        'city_id' => $city->id,
    ]);
}

function giveTradeGoods(User $user, Item $item, int $quantity): void
{
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);
}

function carriedTradeGoods(User $user, Item $item): int
{
    return (int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity');
}

/**
 * Match a toast-show event by its variant and, when given, its text.
 */
function tradeToast(string $variant, ?string $text = null): Closure
{
    return fn (string $event, array $params): bool => ($params['dataset']['variant'] ?? null) === $variant
        && $params['duration'] === 10000
        && ($text === null || $params['slots']['text'] === $text);
}

function openTradeBetween(User $initiator, User $recipient): Trade
{
    return Trade::factory()->open()->create([
        'initiator_id' => $initiator->id,
        'recipient_id' => $recipient->id,
        'city_id' => $initiator->location->city_id,
    ]);
}

test('two players at the same trade post can request, accept, fill and commit a trade', function () {
    Event::fake([TradeUpdated::class]);
    $sasha = traderIn('Warsaw', ['name' => 'Sasha']);
    $niko = traderIn('Warsaw', ['name' => 'Niko']);
    $scrap = Item::factory()->create(['name' => 'Scrap Bundle']);
    $water = Item::factory()->create(['name' => 'Water Flask']);
    giveTradeGoods($sasha, $scrap, 5);
    giveTradeGoods($niko, $water, 2);

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->assertSee('Trade Post · Warsaw')
        ->assertSee('Niko')
        ->call('requestTrade', $niko->id)
        ->assertNotDispatched('toast-show', tradeToast('danger'))
        ->assertSee('Waiting for Niko');

    $trade = Trade::query()->sole();

    $this->actingAs($niko);
    $nikoBoard = Livewire::test(TradeBoard::class)
        ->assertSee('Sasha wants to trade')
        ->call('accept', $trade->id)
        ->assertNotDispatched('toast-show', tradeToast('danger'))
        ->assertSee('Trading with Sasha')
        ->set("offerQuantities.{$water->id}", 2)
        ->call('offer', $water->id)
        ->assertNotDispatched('toast-show', tradeToast('danger'));

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->assertSee('Trading with Niko')
        ->assertSee('Water Flask')
        ->set("offerQuantities.{$scrap->id}", 3)
        ->call('offer', $scrap->id)
        ->call('commit')
        ->assertNotDispatched('toast-show', tradeToast('danger'))
        ->assertDispatched('toast-show', tradeToast('success', 'Offer committed. Waiting for the other trader to commit.'));

    expect($trade->fresh()->status)->toBe(TradeStatus::Open)
        ->and(carriedTradeGoods($sasha, $water))->toBe(0);

    $this->actingAs($niko);
    $nikoBoard->call('commit')
        ->assertNotDispatched('toast-show', tradeToast('danger'))
        ->assertDispatched('toast-show', tradeToast('success', 'Trade complete. The items are in your pack.'));

    expect($trade->fresh()->status)->toBe(TradeStatus::Completed)
        ->and(carriedTradeGoods($sasha, $scrap))->toBe(2)
        ->and(carriedTradeGoods($sasha, $water))->toBe(2)
        ->and(carriedTradeGoods($niko, $scrap))->toBe(3)
        ->and($niko->inventoryItems()->where('item_id', $water->id)->exists())->toBeFalse();

    Event::assertDispatchedTimes(TradeUpdated::class, 6);
});

test('trade updates are broadcast on both traders private channels', function () {
    $trade = Trade::factory()->create();

    $event = new TradeUpdated($trade);

    expect(collect($event->broadcastOn())->pluck('name')->all())->toBe([
        'private-App.Models.User.'.$trade->initiator_id,
        'private-App.Models.User.'.$trade->recipient_id,
    ])->and($event->broadcastWith())->toBe(['id' => $trade->id, 'status' => 'pending']);
});

test('the trade board refreshes when the other trader changes the trade', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw', ['name' => 'Niko']);

    $this->actingAs($sasha);
    $component = Livewire::test(TradeBoard::class)
        ->assertDontSee('Niko wants to trade');

    $trade = Trade::factory()->create(['initiator_id' => $niko->id, 'recipient_id' => $sasha->id]);

    $component->dispatch("echo-private:App.Models.User.{$sasha->id},TradeUpdated", ['id' => $trade->id, 'status' => 'pending'])
        ->assertSee('Niko wants to trade');
});

test('a trade request needs both players at the same trade post', function (string $initiatorCity, string $recipientCity, string $message) {
    $initiator = traderIn($initiatorCity);
    $recipient = traderIn($recipientCity, ['name' => 'Niko']);

    $this->actingAs($initiator);
    Livewire::test(TradeBoard::class)
        ->call('requestTrade', $recipient->id)
        ->assertDispatched('toast-show', tradeToast('danger', $message));

    expect(Trade::query()->count())->toBe(0);
})->with([
    'no trade post here' => ['Kyiv', 'Kyiv', 'You need to be at a trade post to trade.'],
    'partner elsewhere' => ['Warsaw', 'Kyiv', 'Niko is not at the Warsaw trade post.'],
]);

test('players away from a trade post are pointed at the nearest one on their continent', function (string $cityName, string $suggestion) {
    $this->actingAs(traderIn($cityName));

    Livewire::test(TradeBoard::class)
        ->assertSee('Market Offline')
        ->assertSeeInOrder(['Nearest trade post on', $suggestion]);
})->with([
    'Kyiv goes to Warsaw' => ['Kyiv', 'Warsaw, Poland'],
    'Sapporo goes to Tokyo' => ['Sapporo', 'Tokyo, Japan'],
    'Detroit goes to Seattle' => ['Detroit', 'Seattle, United States'],
]);

test('each continent has exactly one trade post', function () {
    $tradePostsByContinent = City::query()
        ->where('has_trade_post', true)
        ->with('country')
        ->get()
        ->countBy(fn (City $city): string => $city->country->continent);

    expect($tradePostsByContinent->all())->toEqualCanonicalizing([
        'Europe' => 1,
        'North America' => 1,
        'South America' => 1,
        'Asia' => 1,
        'Africa' => 1,
        'Oceania' => 1,
    ]);
});

test('the recipient can reject a trade request', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw');
    $trade = Trade::factory()->create(['initiator_id' => $sasha->id, 'recipient_id' => $niko->id]);

    $this->actingAs($niko);
    Livewire::test(TradeBoard::class)
        ->call('cancel', $trade->id)
        ->assertNotDispatched('toast-show', tradeToast('danger'))
        ->assertDispatched('toast-show', tradeToast('success', 'Trade request declined.'));

    expect($trade->fresh()->status)->toBe(TradeStatus::Declined);
});

test('a player can only send one trade request at a time', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw');
    $olek = traderIn('Warsaw');
    Trade::factory()->create(['initiator_id' => $sasha->id, 'recipient_id' => $niko->id]);

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->call('requestTrade', $olek->id)
        ->assertDispatched('toast-show', tradeToast('danger', 'You already have a trade request out. Cancel it before sending another.'));

    expect(Trade::query()->count())->toBe(1);
});

test('players cannot act on trades they are not part of', function () {
    $trade = Trade::factory()->create();

    $this->actingAs(traderIn('Warsaw'));
    Livewire::test(TradeBoard::class)
        ->call('accept', $trade->id)
        ->assertNotFound();

    expect($trade->fresh()->status)->toBe(TradeStatus::Pending);
});

test('quest items cannot be offered and are marked in the pack', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw');
    $keycard = Item::factory()->questItem()->create(['name' => 'Encrypted Keycard']);
    giveTradeGoods($sasha, $keycard, 1);
    openTradeBetween($sasha, $niko);

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->assertSeeInOrder(['Encrypted Keycard', 'Quest item'])
        ->set("offerQuantities.{$keycard->id}", 1)
        ->call('offer', $keycard->id)
        ->assertDispatched('toast-show', tradeToast('danger', 'Encrypted Keycard is a quest item and cannot be traded.'));

    expect(Trade::query()->sole()->items()->count())->toBe(0);
});

test('players cannot offer more than they carry', function () {
    $sasha = traderIn('Warsaw');
    $scrap = Item::factory()->create(['name' => 'Scrap Bundle']);
    giveTradeGoods($sasha, $scrap, 2);
    openTradeBetween($sasha, traderIn('Warsaw'));

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->set("offerQuantities.{$scrap->id}", 3)
        ->call('offer', $scrap->id)
        ->assertDispatched('toast-show', tradeToast('danger', 'You only carry 2x Scrap Bundle.'));

    expect(Trade::query()->sole()->items()->count())->toBe(0);
});

test('changing the table withdraws both commitments', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw');
    $scrap = Item::factory()->create();
    giveTradeGoods($sasha, $scrap, 5);
    $trade = openTradeBetween($sasha, $niko);
    $trade->items()->create(['user_id' => $sasha->id, 'item_id' => $scrap->id, 'quantity' => 1]);
    $trade->update(['recipient_committed_at' => now()]);

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->call('removeOffer', $scrap->id)
        ->assertNotDispatched('toast-show', tradeToast('danger'));

    expect($trade->fresh()->recipient_committed_at)->toBeNull()
        ->and($trade->items()->count())->toBe(0);
});

test('an empty table cannot be committed', function () {
    $sasha = traderIn('Warsaw');
    $trade = openTradeBetween($sasha, traderIn('Warsaw'));

    $this->actingAs($sasha);
    Livewire::test(TradeBoard::class)
        ->call('commit')
        ->assertDispatched('toast-show', tradeToast('danger', 'Put at least one item on the table before committing.'));

    expect($trade->fresh()->initiator_committed_at)->toBeNull();
});

test('a trade does not complete when a trader no longer carries what they offered', function () {
    $sasha = traderIn('Warsaw', ['name' => 'Sasha']);
    $niko = traderIn('Warsaw');
    $scrap = Item::factory()->create(['name' => 'Scrap Bundle']);
    $water = Item::factory()->create();
    giveTradeGoods($sasha, $scrap, 1);
    giveTradeGoods($niko, $water, 1);
    $trade = openTradeBetween($sasha, $niko);
    $trade->items()->create(['user_id' => $sasha->id, 'item_id' => $scrap->id, 'quantity' => 3]);
    $trade->items()->create(['user_id' => $niko->id, 'item_id' => $water->id, 'quantity' => 1]);
    $trade->update(['initiator_committed_at' => now()]);

    $this->actingAs($niko);
    Livewire::test(TradeBoard::class)
        ->call('commit')
        ->assertDispatched('toast-show', tradeToast('danger', 'Sasha no longer carries 3x Scrap Bundle. Update the offer and commit again.'));

    expect($trade->fresh()->status)->toBe(TradeStatus::Open)
        ->and($trade->fresh()->recipient_committed_at)->toBeNull()
        ->and(carriedTradeGoods($sasha, $scrap))->toBe(1)
        ->and(carriedTradeGoods($niko, $water))->toBe(1);
});

test('a trade does not complete once a trader has left the trade post', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw');
    $water = Item::factory()->create();
    giveTradeGoods($niko, $water, 1);
    $trade = openTradeBetween($sasha, $niko);
    $trade->items()->create(['user_id' => $niko->id, 'item_id' => $water->id, 'quantity' => 1]);
    $trade->update(['initiator_committed_at' => now()]);
    moveTraderTo($sasha, 'Gdansk');

    $this->actingAs($niko);
    Livewire::test(TradeBoard::class)
        ->call('commit')
        ->assertDispatched('toast-show', tradeToast('danger'));

    expect($trade->fresh()->status)->toBe(TradeStatus::Open)
        ->and(carriedTradeGoods($niko, $water))->toBe(1);
});

test('cancelling an open trade moves no items', function () {
    $sasha = traderIn('Warsaw');
    $niko = traderIn('Warsaw');
    $scrap = Item::factory()->create();
    giveTradeGoods($sasha, $scrap, 4);
    $trade = openTradeBetween($sasha, $niko);
    $trade->items()->create(['user_id' => $sasha->id, 'item_id' => $scrap->id, 'quantity' => 4]);

    $this->actingAs($niko);
    Livewire::test(TradeBoard::class)
        ->call('cancel', $trade->id)
        ->assertDispatched('toast-show', tradeToast('success', 'Trade cancelled. No items changed hands.'));

    expect($trade->fresh()->status)->toBe(TradeStatus::Cancelled)
        ->and(carriedTradeGoods($sasha, $scrap))->toBe(4)
        ->and(carriedTradeGoods($niko, $scrap))->toBe(0);
});

test('roles without trade access cannot trade', function () {
    $guest = User::factory()->asRole('guest')->create();
    moveTraderTo($guest, 'Warsaw');
    $niko = traderIn('Warsaw');

    $this->actingAs($guest);
    Livewire::test(TradeBoard::class)
        ->assertDontSee('Traders at this post')
        ->call('requestTrade', $niko->id)
        ->assertDispatched('toast-show', tradeToast('danger'));

    expect(Trade::query()->count())->toBe(0);
});

test('admins can mark an item as a quest item', function () {
    $item = Item::factory()->create();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageItems::class)
        ->call('edit', $item->id)
        ->assertSet('is_quest_item', false)
        ->set('is_quest_item', true)
        ->call('save')
        ->assertNotDispatched('toast-show', tradeToast('danger'));

    expect($item->fresh()->is_quest_item)->toBeTrue();
});

test('admins can give a city a trade post', function () {
    $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();

    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageLocations::class)
        ->call('edit', $kyiv->id)
        ->assertSet('has_trade_post', false)
        ->set('has_trade_post', true)
        ->call('save')
        ->assertNotDispatched('toast-show', tradeToast('danger'));

    expect($kyiv->fresh()->has_trade_post)->toBeTrue();
});
