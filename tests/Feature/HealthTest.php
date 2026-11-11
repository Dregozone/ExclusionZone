<?php

use App\Actions\Game\CalculateWorkDuration;
use App\Actions\Game\TrackSurvivalNeeds;
use App\Events\SurvivorDied;
use App\HarmCause;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Facades\Event;

/**
 * A survivor standing in Kyiv in clear weather, so only their condition changes timers.
 */
function survivorWithHealth(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->location->city->update(['weather' => Weather::Clear]);

    return $user->fresh();
}

function quickPatrols(): CityAction
{
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'hunger_cost' => 0, 'fatigue_cost' => 0]);

    return $action->fresh();
}

// ── Losing health ────────────────────────────────────────────────────────────

test('new survivors start at full health', function () {
    expect(User::factory()->create()->fresh()->health)->toBe(100);
});

test('each work cycle finished while starving or spent costs health', function (int $hunger, int $fatigue, int $expectedHealth) {
    config(['survival.health.lost_while_starving' => 4, 'survival.health.lost_while_spent' => 6]);
    $user = survivorWithHealth(['hunger' => $hunger, 'fatigue' => $fatigue]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => quickPatrols()->id]);

    $this->travel(201)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh()->health)->toBe($expectedHealth);
})->with([
    'fed and rested' => [99, 99, 100],
    'starving' => [100, 0, 96],
    'spent' => [0, 100, 94],
    'starving and spent' => [100, 100, 90],
]);

test('a travel leg finished while starving costs health', function () {
    config(['survival.health.lost_while_starving' => 7]);
    $user = survivorWithHealth(['hunger' => 100]);
    $destination = $user->location->city->neighbors()->firstOrFail();
    $destination->update(['radiation_damage' => 0]);
    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);

    $this->travel(1)->days();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh())
        ->health->toBe(93)
        ->location->city_id->toBe($destination->id);
});

// ── Slower timers ────────────────────────────────────────────────────────────

test('work timers grow with the lowest health threshold reached', function (int $health, int $expectedSeconds) {
    $user = survivorWithHealth(['health' => $health]);

    expect(app(CalculateWorkDuration::class)->forCityAction($user, quickPatrols()))->toBe($expectedSeconds);
})->with([
    'healthy' => [51, 100],
    'wounded' => [50, 110],
    'badly hurt' => [25, 125],
    'critical' => [10, 150],
]);

// ── Healing ──────────────────────────────────────────────────────────────────

test('eating food restores its health value up to the maximum', function (int $health, int $expectedHealth, string $status) {
    $user = survivorWithHealth(['health' => $health]);
    $borscht = Item::query()->where('key', 'borscht')->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $borscht->id, 'quantity' => 1]);

    $this->actingAs($user)->post(route('food.eat'), ['item_id' => $borscht->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', $status);

    expect($user->fresh()->health)->toBe($expectedHealth);
})->with([
    'hurt' => [40, 55, 'You ate 1x Borscht (health +15).'],
    'nearly healthy' => [95, 100, 'You ate 1x Borscht (health +5).'],
]);

// ── Death ────────────────────────────────────────────────────────────────────

test('reaching 0 health fires the death event with its cause', function () {
    Event::fake([SurvivorDied::class]);
    $user = survivorWithHealth(['health' => 5]);

    $died = app(TrackSurvivalNeeds::class)->harm($user, 8, HarmCause::Exhaustion);

    expect($died)->toBeTrue()
        ->and($user->fresh()->health)->toBe(0);
    Event::assertDispatched(SurvivorDied::class, fn (SurvivorDied $event): bool => $event->user->is($user) && $event->cause === HarmCause::Exhaustion);
});

test('harm that leaves health above 0 does not fire the death event', function () {
    Event::fake([SurvivorDied::class]);
    $user = survivorWithHealth(['health' => 9]);

    expect(app(TrackSurvivalNeeds::class)->harm($user, 8, HarmCause::Starvation))->toBeFalse();

    Event::assertNotDispatched(SurvivorDied::class);
});

test('dying during work stops it, revives the survivor and tells them on the dashboard', function () {
    config(['survival.health.lost_while_starving' => 10, 'survival.health.revive_health' => 30]);
    $user = survivorWithHealth(['hunger' => 100, 'health' => 15]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => quickPatrols()->id]);

    $this->travel(1)->hours();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh())
        ->health->toBe(30)
        ->activeWork->toBeNull()
        ->death_report->toContain('Hunger finally took your legs out from under you');

    $this->actingAs($user->fresh())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="death-report"', false)
        ->assertSeeText('Be more careful out there.');
});

test('dying on the road ends the route at the stop reached when no trade post is near', function () {
    config(['survival.health.lost_while_spent' => 50]);
    City::query()->update(['has_trade_post' => false]);
    $user = survivorWithHealth(['fatigue' => 100, 'health' => 40]);
    $origin = $user->location->city;
    $stopover = $origin->neighbors()->get()
        ->first(fn (City $neighbor): bool => $neighbor->neighbors()->whereKeyNot($origin->id)->exists());
    $destination = $stopover->neighbors()->whereKeyNot($origin->id)->firstOrFail();
    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id, 'via' => [$stopover->id]]);

    $this->travel(2)->days();
    $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSeeText('You died');

    expect($user->fresh())
        ->activeWork->toBeNull()
        ->location->city_id->toBe($stopover->id)
        ->death_report->toContain('exhaustion');
});

test('a fallen survivor wakes at the nearest trade post with part of their pack gone', function () {
    config(['survival.death.pack_lost_percent' => 25]);
    $user = survivorWithHealth(['health' => 5]);
    $scrap = Item::query()->where('key', 'scrap_metal')->firstOrFail();
    $borscht = Item::query()->where('key', 'borscht')->firstOrFail();
    $questItem = Item::factory()->questItem()->create(['name' => 'Sealed Dossier']);
    $user->inventoryItems()->createMany([
        ['item_id' => $scrap->id, 'quantity' => 8],
        ['item_id' => $borscht->id, 'quantity' => 3],
        ['item_id' => $questItem->id, 'quantity' => 8],
    ]);

    app(TrackSurvivalNeeds::class)->harm($user, 5, HarmCause::Radiation);

    $user = $user->fresh()->load('inventoryItems.item', 'location.city');
    expect($user->location->city->city)->toBe('Warsaw')
        ->and($user->inventoryItems->mapWithKeys(fn ($inventoryItem): array => [$inventoryItem->item->name => $inventoryItem->quantity])->all())
        ->toEqual(['Scrap Metal' => 6, 'Borscht' => 3, 'Sealed Dossier' => 8])
        ->and($user->death_report)
        ->toContain('The radiation burned through you')
        ->toContain('carried you all the way to the trade post in Warsaw')
        ->toContain('you lost 2x Scrap Metal.');
});

test('a fallen survivor cannot work or travel until they have recovered', function () {
    config(['survival.death.recovery_minutes' => 10]);
    $user = survivorWithHealth(['health' => 5]);
    app(TrackSurvivalNeeds::class)->harm($user, 5, HarmCause::Storm);
    $user = $user->fresh();
    $warsawAction = CityAction::query()->whereBelongsTo($user->location->city)->where('min_level', 1)->firstOrFail();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertSeeText('You are still recovering from your collapse.');
    $this->post(route('city-action.store'), ['city_action_id' => $warsawAction->id])
        ->assertSessionHas('toast', fn (array $toast): bool => str_starts_with($toast['text'], 'You are still recovering'));
    expect($user->activeWork()->exists())->toBeFalse();

    $this->travel(11)->minutes();

    $this->post(route('city-action.store'), ['city_action_id' => $warsawAction->id]);
    expect($user->activeWork()->exists())->toBeTrue();
});

test('survivors dismiss the death report once they have read it', function () {
    $user = survivorWithHealth();
    $user->forceFill(['death_report' => 'You collapsed.'])->save();

    $this->actingAs($user)->post(route('death-report.dismiss'))->assertRedirect(route('dashboard'));

    expect($user->fresh()->death_report)->toBeNull();
    $this->actingAs($user)->get(route('dashboard'))->assertDontSee('data-test="death-report"', false);
});

test('guests are sent to log in before dismissing a death report', function () {
    $this->post(route('death-report.dismiss'))->assertRedirect(route('login'));
});

// ── Dashboard ────────────────────────────────────────────────────────────────

test('the dashboard shows health in the survivor condition', function () {
    $user = survivorWithHealth(['health' => 20, 'rested_at' => now()]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Badly hurt · 20 / 100')
        ->assertSeeText('Timers +25%');
});
