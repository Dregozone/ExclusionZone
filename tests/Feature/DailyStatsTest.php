<?php

use App\Models\Login;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config(['services.stats.token' => 'secret-token']);
});

function statsRequest(string $query = '', string $token = 'secret-token')
{
    return test()->withToken($token)->getJson('/api/stats/daily'.$query);
}

test('stats endpoint is off when no token is configured', function () {
    config(['services.stats.token' => null]);

    statsRequest()->assertNotFound();
});

test('stats endpoint rejects a wrong or missing token', function () {
    statsRequest('', 'wrong')->assertUnauthorized();
    $this->getJson('/api/stats/daily')->assertUnauthorized();
});

test('stats endpoint returns counts for the requested day', function () {
    $inside = User::factory()->create(['created_at' => '2026-10-12 10:00:00']);
    User::factory()->create(['created_at' => '2026-10-12 11:00:00']);
    User::factory()->create(['created_at' => '2026-10-10 11:00:00']);
    User::factory()->create(['created_at' => '2026-10-13 11:00:00']);

    Login::create(['user_id' => $inside->id, 'created_at' => '2026-10-12 09:00:00']);
    Login::create(['user_id' => $inside->id, 'created_at' => '2026-10-12 12:00:00']);
    Login::create(['user_id' => User::query()->latest('id')->value('id'), 'created_at' => '2026-10-13 12:00:00']);

    statsRequest('?date=2026-10-12')
        ->assertOk()
        ->assertJsonPath('app', 'Exclusion Zone')
        ->assertJsonPath('date', '2026-10-12')
        ->assertJsonPath('signups', 2)
        ->assertJsonPath('logins.total', 2)
        ->assertJsonPath('logins.unique', 1)
        ->assertJsonPath('users.total', 3);
});

test('stats endpoint counts active users in 7 and 30 day windows', function () {
    User::factory()->create(['last_active_at' => '2026-10-12 08:00:00']);
    User::factory()->create(['last_active_at' => '2026-10-02 08:00:00']);
    User::factory()->create(['last_active_at' => '2026-08-01 08:00:00']);

    statsRequest('?date=2026-10-12')
        ->assertJsonPath('users.active_7d', 1)
        ->assertJsonPath('users.active_30d', 2);
});

test('stats endpoint defaults to yesterday', function () {
    Carbon::setTestNow('2026-10-15 12:00:00');

    statsRequest()->assertOk()->assertJsonPath('date', '2026-10-14');

    Carbon::setTestNow();
});

test('stats endpoint rejects an invalid date', function () {
    statsRequest('?date=nope')->assertUnprocessable();
});

test('logging in records a login without extra data', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

    expect(Login::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(Login::query()->first()->getAttributes())->toHaveKeys(['id', 'user_id', 'created_at'])
        ->and(count(Login::query()->first()->getAttributes()))->toBe(3);
});

test('authenticated requests update last active at at most hourly', function () {
    $user = User::factory()->create();

    Carbon::setTestNow('2026-10-15 12:00:00');
    $this->actingAs($user)->get(route('dashboard'));
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-10-15 12:00:00');

    Carbon::setTestNow('2026-10-15 12:30:00');
    $this->actingAs($user)->get(route('dashboard'));
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-10-15 12:00:00');

    Carbon::setTestNow('2026-10-15 13:30:00');
    $this->actingAs($user)->get(route('dashboard'));
    expect($user->fresh()->last_active_at->toDateTimeString())->toBe('2026-10-15 13:30:00');

    Carbon::setTestNow();
});
