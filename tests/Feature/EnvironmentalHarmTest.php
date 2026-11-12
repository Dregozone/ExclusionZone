<?php

use App\Events\SurvivorDied;
use App\HarmCause;
use App\Livewire\Admin\ManageCityActions;
use App\Livewire\Admin\ManageLocations;
use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Lottery;
use Livewire\Livewire;

/**
 * A survivor standing in Kyiv, with Kyiv's weather set as given.
 */
function survivorInKyiv(Weather $weather = Weather::Clear, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->location->city->update(['weather' => $weather]);

    return $user->fresh();
}

/**
 * District patrols with a short timer, no hunger or fatigue cost, and the hazard given.
 */
function patrolsWithHazard(int $chancePercent = 0, int $damage = 0): CityAction
{
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'hunger_cost' => 0, 'fatigue_cost' => 0, 'hazard_chance_pct' => $chancePercent, 'hazard_damage' => $damage]);

    return $action->fresh();
}

// ── Work ─────────────────────────────────────────────────────────────────────

test('a work cycle finished in a storm costs health when the storm roll hits', function (bool $stormHits, int $expectedHealth) {
    config(['survival.environment.storm_harm_chance_pct' => 20, 'survival.environment.storm_damage' => 6]);
    $user = survivorInKyiv(Weather::Storm);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => patrolsWithHazard()->id]);
    $stormHits ? Lottery::alwaysWin() : Lottery::alwaysLose();

    $this->travel(200)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh()->health)->toBe($expectedHealth);
})->with([
    'storm hits' => [true, 94],
    'storm misses' => [false, 100],
]);

test('safe work in clear weather outside radiation never costs health', function () {
    $user = survivorInKyiv(Weather::Clear);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => patrolsWithHazard()->id]);
    Lottery::alwaysWin();

    $this->travel(200)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh()->health)->toBe(100);
});

test('an accident on hazardous work costs its damage and is reported with the cycle', function () {
    $user = survivorInKyiv();
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => patrolsWithHazard(30, 7)->id]);
    Lottery::alwaysWin();

    $this->travel(100)->seconds();

    $this->post(route('work.complete'))
        ->assertSessionHas('status', fn (string $status): bool => str_ends_with($status, 'You were hurt on the job (-7 health).'));
    expect($user->fresh()->health)->toBe(93);
});

test('every work cycle in an irradiated city costs its radiation damage', function () {
    $user = survivorInKyiv();
    $user->location->city->update(['radiation_damage' => 3]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => patrolsWithHazard()->id]);
    Lottery::alwaysLose();

    $this->travel(200)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh()->health)->toBe(94);
});

test('an accident that takes the last of the survivor health kills them', function () {
    Event::fake([SurvivorDied::class]);
    $user = survivorInKyiv(attributes: ['health' => 5]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => patrolsWithHazard(30, 10)->id]);
    Lottery::alwaysWin();

    $this->travel(150)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh()->health)->toBe(0);
    Event::assertDispatched(SurvivorDied::class, fn (SurvivorDied $event): bool => $event->cause === HarmCause::Accident);
});

// ── Travel ───────────────────────────────────────────────────────────────────

test('arriving in an irradiated city costs its radiation damage', function () {
    $user = survivorInKyiv();
    $destination = City::query()->where('city', 'Pripyat')->firstOrFail();
    $destination->update(['radiation_damage' => 4]);
    Lottery::alwaysLose();
    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);

    $this->travel(1)->days();

    $this->post(route('work.complete'))
        ->assertSessionHas('status', 'You arrived in Pripyat and refreshed your options. Radiation seeped into you (-4 health).');
    expect($user->fresh()->health)->toBe(96);
});

test('a travel leg walked in a storm costs health when the storm roll hits', function () {
    config(['survival.environment.storm_harm_chance_pct' => 20, 'survival.environment.storm_damage' => 6]);
    $user = survivorInKyiv(Weather::Storm);
    $destination = City::query()->where('city', 'Warsaw')->firstOrFail();
    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);
    Lottery::alwaysWin();

    $this->travel(1)->days();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()->health)->toBe(94);
});

// ── Admin ────────────────────────────────────────────────────────────────────

test('admins set the accident chance and damage of a city action', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('hazard_chance_pct', 15)
        ->set('hazard_damage', 8)
        ->call('save')
        ->assertHasNoErrors();

    expect($action->fresh())
        ->hazard_chance_pct->toBe(15)
        ->hazard_damage->toBe(8);
});

test('admins cannot set an accident chance or damage outside 0 to 100', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('hazard_chance_pct', 101)
        ->set('hazard_damage', -1)
        ->call('save')
        ->assertHasErrors(['hazard_chance_pct' => 'max', 'hazard_damage' => 'min']);
});

test('admins set the radiation damage of a city', function () {
    $admin = User::factory()->admin()->create();
    $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageLocations::class)
        ->call('edit', $kyiv->id)
        ->assertSet('radiation_damage', 0)
        ->set('radiation_damage', 5)
        ->call('save')
        ->assertHasNoErrors();

    expect($kyiv->fresh()->radiation_damage)->toBe(5);
});
