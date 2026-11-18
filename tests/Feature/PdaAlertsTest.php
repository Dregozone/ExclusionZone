<?php

use App\Actions\Game\SetTradeOffer;
use App\Actions\Game\TrackPdaAlerts;
use App\Livewire\RadioChat;
use App\Livewire\TradeBoard;
use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\RadioMessage;
use App\Models\Role;
use App\Models\Trade;
use App\Models\User;
use Livewire\Livewire;

function survivorAtTradePost(): User
{
    $user = User::factory()->create();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $user->location()->update(['country_id' => $warsaw->country_id, 'city_id' => $warsaw->id]);

    return $user->fresh();
}

function alertCounts(User $user): array
{
    return app(TrackPdaAlerts::class)->counts($user->fresh());
}

// ── Radio ────────────────────────────────────────────────────────────────────

test('radio messages from other survivors raise an alert until the radio tab is opened', function () {
    $listener = User::factory()->create();
    RadioMessage::factory()->count(2)->create();
    RadioMessage::factory()->create(['user_id' => $listener->id]);

    expect(alertCounts($listener)['radio'])->toBe(2);

    Livewire::actingAs($listener)->test(RadioChat::class)->dispatch('pda-tab-seen', tab: 'radio');

    expect(alertCounts($listener)['radio'])->toBe(0);

    RadioMessage::factory()->create();

    expect(alertCounts($listener)['radio'])->toBe(1);
});

test('opening another tab leaves the radio alert in place', function () {
    $listener = User::factory()->create();
    RadioMessage::factory()->create();

    Livewire::actingAs($listener)->test(RadioChat::class)->dispatch('pda-tab-seen', tab: 'trade');

    expect(alertCounts($listener)['radio'])->toBe(1);
});

test('sending a radio message marks the radio as seen', function () {
    $speaker = User::factory()->create();
    RadioMessage::factory()->create();

    Livewire::actingAs($speaker)->test(RadioChat::class)->set('message', 'Copy that.')->call('send');

    expect(alertCounts($speaker)['radio'])->toBe(0);
});

// ── Trades ───────────────────────────────────────────────────────────────────

test('a trade request raises an alert for the survivor asked, not the one asking', function () {
    $sasha = survivorAtTradePost();
    $niko = survivorAtTradePost();

    Livewire::actingAs($sasha)->test(TradeBoard::class)->call('requestTrade', $niko->id);

    expect(alertCounts($niko)['trade'])->toBe(1)
        ->and(alertCounts($sasha)['trade'])->toBe(0);

    Livewire::actingAs($niko)->test(TradeBoard::class)->dispatch('pda-tab-seen', tab: 'trade');

    expect(alertCounts($niko)['trade'])->toBe(0);
});

test('a change to the items on the table raises the trade alert again', function () {
    $sasha = survivorAtTradePost();
    $niko = survivorAtTradePost();
    $trade = Trade::factory()->open()->create(['initiator_id' => $sasha->id, 'recipient_id' => $niko->id, 'city_id' => $sasha->location->city_id]);
    app(TrackPdaAlerts::class)->markSeen($niko, 'trade');
    $scrap = Item::query()->where('key', 'scrap_metal')->firstOrFail();
    $sasha->inventoryItems()->create(['item_id' => $scrap->id, 'quantity' => 2]);

    $this->travel(5)->seconds();
    app(SetTradeOffer::class)($sasha, $trade, $scrap, 2);

    expect(alertCounts($niko)['trade'])->toBe(1);
});

// ── The bell ─────────────────────────────────────────────────────────────────

test('the PDA button shows a red bell with the number of alerts', function () {
    $listener = User::factory()->create();
    RadioMessage::factory()->count(3)->create();

    $this->actingAs($listener)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['data-test="pda-alert-badge"', '3'], false)
        ->assertSee('data-test="pda-tab-alert-radio"', false);
});

test('survivors who cannot use the radio get no radio alerts', function () {
    $guest = User::factory()->create();
    $guest->role()->associate(Role::query()->where('key', 'guest')->firstOrFail())->save();
    RadioMessage::factory()->create();

    expect(alertCounts($guest)['radio'])->toBe(0);
});

// ── Jobs ─────────────────────────────────────────────────────────────────────

/**
 * A survivor with only the given quests in the world, so seeded story chapters don't raise alerts.
 */
function jobSeeker(): User
{
    Quest::query()->update(['is_active' => false]);

    return User::factory()->create();
}

test('a newly unlocked story chapter raises a jobs alert until the jobs tab is opened', function () {
    $survivor = jobSeeker();
    $firstChapter = Quest::factory()->create(['quest_type' => 'story', 'sequence_order' => 1]);

    expect(alertCounts($survivor)['contracts'])->toBe(1);

    $this->actingAs($survivor)->post(route('pda-alerts.seen', 'contracts'))->assertNoContent();

    expect(alertCounts($survivor)['contracts'])->toBe(0);

    $survivor->userQuests()->create(['quest_id' => $firstChapter->id, 'status' => 'completed', 'current_step_index' => 0]);
    Quest::factory()->create(['quest_type' => 'story', 'sequence_order' => 2, 'prerequisite_quest_id' => $firstChapter->id]);

    expect(alertCounts($survivor)['contracts'])->toBe(1);
});

test('a job step ready to hand in where the survivor stands raises a jobs alert', function () {
    $survivor = jobSeeker();
    $job = Quest::factory()->create();
    QuestStep::factory()->create(['quest_id' => $job->id, 'city_id' => $survivor->location->city_id]);
    $survivor->userQuests()->create(['quest_id' => $job->id, 'status' => 'active', 'current_step_index' => 0]);

    expect(alertCounts($survivor)['contracts'])->toBe(1);

    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $survivor->location()->update(['country_id' => $warsaw->country_id, 'city_id' => $warsaw->id]);

    expect(alertCounts($survivor)['contracts'])->toBe(0);
});

test('survivors who cannot work get no jobs alerts', function () {
    $guest = jobSeeker();
    $guest->role()->associate(Role::query()->where('key', 'guest')->firstOrFail())->save();
    Quest::factory()->create(['quest_type' => 'story']);

    expect(alertCounts($guest)['contracts'])->toBe(0);
});

// ── Polling ──────────────────────────────────────────────────────────────────

test('the PDA can ask for fresh alert counts without reloading the page', function () {
    $listener = jobSeeker();
    RadioMessage::factory()->count(2)->create();

    $this->actingAs($listener)
        ->getJson(route('pda-alerts.index'))
        ->assertOk()
        ->assertExactJson(['radio' => 2, 'trade' => 0, 'contracts' => 0]);
});

test('guests are sent to log in before asking for alert counts', function () {
    $this->get(route('pda-alerts.index'))->assertRedirect(route('login'));
});

test('only PDA tabs that raise alerts can be marked as seen', function () {
    $this->actingAs(User::factory()->create())
        ->post('/dashboard/pda-alerts/pack/seen')
        ->assertNotFound();
});
