<?php

use App\Actions\Game\RollCityWeather;
use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use App\Weather;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;

function setCityWeather(string $cityName, Weather $weather): City
{
    $city = City::query()->where('city', $cityName)->firstOrFail();
    $city->update(['weather' => $weather]);

    return $city;
}

// ── Rolling weather ───────────────────────────────────────────────────────────

test('rolling weather clears every city when the rain roll fails', function () {
    City::query()->update(['weather' => Weather::Storm->value]);
    Lottery::alwaysLose();

    $this->artisan('weather:roll')->assertSuccessful();

    expect(City::query()->where('weather', '!=', Weather::Clear->value)->count())->toBe(0);
});

test('rolling weather brings rain when the rain roll succeeds and the storm roll fails', function () {
    $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();
    Lottery::fix([true, false]);

    $weather = app(RollCityWeather::class)($kyiv);

    expect($weather)->toBe(Weather::Rain)
        ->and($kyiv->fresh()->weather)->toBe(Weather::Rain);
});

test('rolling weather brings a storm when both the rain and storm rolls succeed', function () {
    Lottery::alwaysWin();

    $this->artisan('weather:roll')->assertSuccessful();

    expect(City::query()->where('weather', '!=', Weather::Storm->value)->count())->toBe(0);
});

test('weather is rolled every hour by the scheduler', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains($event->command, 'weather:roll'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 * * * *');
});

// ── Timers ────────────────────────────────────────────────────────────────────

test('bad weather lengthens city action timers', function (Weather $weather, int $expectedDuration) {
    $user = User::factory()->create();
    setCityWeather('Kyiv', $weather);
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $this->actingAs($user)
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh('activeWork')->activeWork)
        ->duration_seconds->toBe($expectedDuration)
        ->weather->toBe($weather);
})->with([
    'clear' => [Weather::Clear, 20],
    'rain' => [Weather::Rain, 23],
    'storm' => [Weather::Storm, 26],
]);

test('travel timers use the weather of the city the route starts from', function () {
    $user = User::factory()->create();
    setCityWeather('Kyiv', Weather::Rain);
    $warsaw = setCityWeather('Warsaw', Weather::Clear);

    DB::table('city_connections')
        ->where('city_id', $user->location->city_id)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 100]);

    $this->actingAs($user)
        ->post(route('travel.store'), ['city_id' => $warsaw->id])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh('activeWork')->activeWork)
        ->duration_seconds->toBe(115)
        ->weather->toBe(Weather::Rain);
});

// ── Rewards ───────────────────────────────────────────────────────────────────

test('work in bad weather grants bonus experience and can lose the loot', function () {
    $user = User::factory()->create();
    setCityWeather('Kyiv', Weather::Storm);
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $this->freezeTime();
    Lottery::alwaysWin();

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);
    $this->travel(27)->seconds();

    $this->post(route('work.complete'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Action completed: +23 XP (+25% Storm bonus), 2 Scrap Metal lost to the storm.');

    $user->refresh()->load('skills.skill', 'inventoryItems.item');

    expect($user->skillFor('scavenging')?->xp)->toBe(23)
        ->and($user->inventoryItems->firstWhere('item.key', 'scrap_metal'))->toBeNull();
});

test('work in bad weather keeps the loot when the loss roll fails', function () {
    $user = User::factory()->create();
    setCityWeather('Kyiv', Weather::Rain);
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $this->freezeTime();
    Lottery::alwaysLose();

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);
    $this->travel(24)->seconds();

    $this->post(route('work.complete'))
        ->assertSessionHas('status', 'Action completed: +20 XP (+10% Rain bonus), +2 Scrap Metal.');

    $user->refresh()->load('inventoryItems.item');

    expect($user->inventoryItems->firstWhere('item.key', 'scrap_metal')?->quantity)->toBe(2);
});

test('work keeps the weather it started in when the city weather changes', function () {
    $user = User::factory()->create();
    setCityWeather('Kyiv', Weather::Rain);
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $this->freezeTime();
    Lottery::alwaysLose();

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);
    setCityWeather('Kyiv', Weather::Clear);
    $this->travel(24)->seconds();

    $this->post(route('work.complete'));

    expect($user->fresh()->skillFor('scavenging')?->xp)->toBe(20);
});

// ── City menu ─────────────────────────────────────────────────────────────────

test('the city menu shows the current weather and its effects', function () {
    $user = User::factory()->create();
    setCityWeather('Kyiv', Weather::Storm);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['Current weather', 'Storm', 'Timers +30% · 25% chance to lose loot · +25% XP'])
        ->assertSee('A storm is tearing through the district');
});
