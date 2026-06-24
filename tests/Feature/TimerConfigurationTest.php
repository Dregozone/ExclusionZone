<?php

use App\Actions\Game\CalculateWorkDuration;
use App\Livewire\Admin\ManageCityActions;
use App\Livewire\Admin\ManageTravelRoutes;
use App\Models\City;
use App\Models\CityAction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

// ── CalculateWorkDuration ─────────────────────────────────────────────────────

test('city action duration reads base_duration_seconds from the action record', function () {
    $calculator = app(CalculateWorkDuration::class);
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $originalDuration = $calculator->forCityAction($user, $action);

    $action->update(['base_duration_seconds' => 60]);
    $action->refresh();

    $updatedDuration = $calculator->forCityAction($user, $action);

    expect($originalDuration)->not->toBe(60)
        ->and($updatedDuration)->toBe(60);
});

test('city action duration is reduced by skill level with 1% per level above 1', function () {
    $calculator = app(CalculateWorkDuration::class);
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 100]);
    $action->refresh();

    $skill = $user->skillFor($action->skill_key);
    $skill->update(['level' => 1]);
    expect($calculator->forCityAction($user, $action))->toBe(100);

    $skill->update(['level' => 11]);
    $user->unsetRelation('skills');
    // 100 * (100 - 10) / 100 = 90
    expect($calculator->forCityAction($user->fresh('skills'), $action))->toBe(90);

    $skill->update(['level' => 51]);
    $user->unsetRelation('skills');
    // 100 * (100 - 50) / 100 = 50
    expect($calculator->forCityAction($user->fresh('skills'), $action))->toBe(50);
});

test('city action duration never goes below 10 seconds regardless of skill level', function () {
    $calculator = app(CalculateWorkDuration::class);
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 10]);
    $action->refresh();

    $skill = $user->skillFor($action->skill_key);
    $skill->update(['level' => 99]);

    expect($calculator->forCityAction($user->fresh('skills'), $action))->toBe(10);
});

test('travel duration reads duration_seconds from the city_connections pivot', function () {
    $calculator = app(CalculateWorkDuration::class);
    $user = User::factory()->create();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();

    $originCityId = $user->location->city_id;

    DB::table('city_connections')
        ->where('city_id', $originCityId)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 120]);

    expect($calculator->forTravel($user->fresh('location'), $warsaw))->toBe(120);

    DB::table('city_connections')
        ->where('city_id', $originCityId)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 30]);
});

test('travel duration never goes below 10 seconds', function () {
    $calculator = app(CalculateWorkDuration::class);
    $user = User::factory()->create();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();

    $originCityId = $user->location->city_id;

    DB::table('city_connections')
        ->where('city_id', $originCityId)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 5]);

    expect($calculator->forTravel($user->fresh('location'), $warsaw))->toBe(10);

    DB::table('city_connections')
        ->where('city_id', $originCityId)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 30]);
});

// ── Integration: custom durations flow into UserWork ─────────────────────────

test('city action with custom base duration creates user work with that duration', function () {
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 75]);

    $this->actingAs($user)
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'));

    $duration = $user->fresh('activeWork')->activeWork?->duration_seconds;
    expect($duration)->toBe(75);

    Carbon::setTestNow(now()->addSeconds($duration + 1));
    $this->post(route('work.complete'))->assertRedirect(route('dashboard'));
    Carbon::setTestNow();
});

test('travel route with custom duration creates user work with that duration', function () {
    $user = User::factory()->create();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $originCityId = $user->location->city_id;

    DB::table('city_connections')
        ->where('city_id', $originCityId)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 90]);

    $this->actingAs($user)
        ->post(route('travel.store'), ['city_id' => $warsaw->id])
        ->assertRedirect(route('dashboard'));

    $duration = $user->fresh('activeWork')->activeWork?->duration_seconds;
    expect($duration)->toBe(90);

    Carbon::setTestNow(now()->addSeconds($duration + 1));
    $this->post(route('work.complete'))->assertRedirect(route('dashboard'));
    Carbon::setTestNow();

    DB::table('city_connections')
        ->where('city_id', $originCityId)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 30]);
});

// ── ManageTravelRoutes Livewire component ────────────────────────────────────

test('manage travel routes is only accessible to admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.travel-routes'))
        ->assertForbidden();

    Livewire::actingAs($user)
        ->test(ManageTravelRoutes::class)
        ->assertForbidden();
});

test('manage travel routes lists all city connections with durations', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ManageTravelRoutes::class)
        ->assertSee('Kyiv')
        ->assertSee('Warsaw')
        ->assertSee('30s');
});

test('admin can update a travel route duration via manage travel routes', function () {
    $admin = User::factory()->admin()->create();
    $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageTravelRoutes::class)
        ->call('edit', $kyiv->id, $warsaw->id, 30)
        ->set('editingDuration', 120)
        ->call('save')
        ->assertHasNoErrors();

    expect(
        DB::table('city_connections')
            ->where('city_id', $kyiv->id)
            ->where('neighbor_city_id', $warsaw->id)
            ->value('duration_seconds')
    )->toBe(120);

    DB::table('city_connections')
        ->where('city_id', $kyiv->id)
        ->where('neighbor_city_id', $warsaw->id)
        ->update(['duration_seconds' => 30]);
});

test('manage travel routes rejects duration below 10 seconds', function () {
    $admin = User::factory()->admin()->create();
    $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageTravelRoutes::class)
        ->call('edit', $kyiv->id, $warsaw->id, 30)
        ->set('editingDuration', 5)
        ->call('save')
        ->assertHasErrors(['editingDuration']);
});

test('manage travel routes cancel clears the editing state', function () {
    $admin = User::factory()->admin()->create();
    $kyiv = City::query()->where('city', 'Kyiv')->firstOrFail();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageTravelRoutes::class)
        ->call('edit', $kyiv->id, $warsaw->id, 30)
        ->assertSet('editingCityId', $kyiv->id)
        ->call('cancel')
        ->assertSet('editingCityId', null);
});

// ── ManageCityActions: base_duration_seconds field ───────────────────────────

test('manage city actions form includes base_duration_seconds and saves it', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->assertSet('base_duration_seconds', $action->base_duration_seconds)
        ->set('base_duration_seconds', 45)
        ->call('save')
        ->assertHasNoErrors();

    expect($action->fresh()->base_duration_seconds)->toBe(45);
});

test('manage city actions rejects base_duration_seconds below 10', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('base_duration_seconds', 5)
        ->call('save')
        ->assertHasErrors(['base_duration_seconds']);
});
