<?php

use App\Models\City;
use App\Models\User;

/**
 * Start a two-leg trip from the player's city, through a neighbor, to a city one step further on.
 *
 * @return array{0: User, 1: City, 2: City, 3: City}
 */
function startTwoLegRoute(): array
{
    $user = User::factory()->create();
    $origin = $user->location->city;
    $directCityIds = $origin->neighbors()->pluck('cities.id')->push($origin->id);
    $stopover = $origin->neighbors()->get()
        ->first(fn (City $neighbor): bool => $neighbor->neighbors()->whereNotIn('cities.id', $directCityIds)->exists());
    $destination = $stopover->neighbors()->whereNotIn('cities.id', $directCityIds)->firstOrFail();

    test()->freezeTime();
    test()->actingAs($user)
        ->post(route('travel.store'), ['city_id' => $destination->id, 'via' => [$stopover->id]])
        ->assertRedirect(route('dashboard'));

    return [$user, $origin, $stopover, $destination];
}

test('a route through other cities starts with the first leg and queues the rest', function () {
    [$user, $origin, $stopover, $destination] = startTwoLegRoute();

    $this->assertDatabaseHas('user_works', [
        'user_id' => $user->id,
        'work_type' => 'travel',
        'origin_city_id' => $origin->id,
        'destination_city_id' => $stopover->id,
    ]);
    expect($user->fresh('activeWork')->activeWork->queuedCityIds())->toBe([$destination->id]);

    $this->get(route('dashboard'))
        ->assertSee($origin->city.' → '.$stopover->city.' → '.$destination->city);
});

test('arriving at a stop sets off on the next leg without the player confirming', function () {
    [$user, , $stopover, $destination] = startTwoLegRoute();
    $firstLeg = $user->fresh('activeWork')->activeWork;

    $this->travel($firstLeg->duration_seconds)->seconds();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee("You arrived in {$stopover->city} and set off for {$destination->city}.");

    $secondLeg = $user->fresh('activeWork')->activeWork;

    expect($user->fresh()->location->city_id)->toBe($stopover->id)
        ->and($secondLeg->origin_city_id)->toBe($stopover->id)
        ->and($secondLeg->destination_city_id)->toBe($destination->id)
        ->and($secondLeg->queuedCityIds())->toBe([])
        ->and($secondLeg->available_at->equalTo($firstLeg->available_at->addSeconds($secondLeg->duration_seconds)))->toBeTrue();

    $this->travel($secondLeg->duration_seconds)->seconds();

    $this->get(route('dashboard'))->assertSee("You arrived in {$destination->city} and refreshed your options.");

    expect($user->fresh()->location->city_id)->toBe($destination->id);
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('a player who was away for the whole route arrives at the final destination', function () {
    [$user, , , $destination] = startTwoLegRoute();

    $this->travel(1)->days();
    $this->get(route('dashboard'))->assertOk();

    expect($user->fresh()->location->city_id)->toBe($destination->id);
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('canceling partway through a route keeps the player at the stop they reached', function () {
    [$user, , $stopover] = startTwoLegRoute();

    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();

    $this->post(route('work.cancel'))
        ->assertSessionHas('status', "Travel canceled. You stayed in {$stopover->city}.");

    expect($user->fresh()->location->city_id)->toBe($stopover->id);
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('a route stops at the reached city when its next leg is no longer connected', function () {
    [$user, , $stopover, $destination] = startTwoLegRoute();
    $stopover->neighbors()->detach($destination->id);

    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();
    $this->get(route('dashboard'))->assertSee("You arrived in {$stopover->city} but could not carry on along your route.");

    expect($user->fresh()->location->city_id)->toBe($stopover->id)
        ->and($user->fresh()->last_work_report)->toContain('That route is not available from your current city.');
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('a single leg arrives on the next dashboard load once its timer ends', function () {
    $user = User::factory()->create();
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $this->freezeTime();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $warsaw->id]);
    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();

    $this->get(route('dashboard'))->assertSee('You arrived in Warsaw and refreshed your options.');

    expect($user->fresh()->location->city_id)->toBe($warsaw->id);
});

test('a route with a gap between its stops is refused', function () {
    $user = User::factory()->create();
    $origin = $user->location->city;
    $unconnected = City::query()
        ->whereKeyNot($origin->id)
        ->whereNotIn('id', $origin->neighbors()->pluck('cities.id'))
        ->firstOrFail();
    $neighbor = $origin->neighbors()->firstOrFail();

    $this->actingAs($user)
        ->post(route('travel.store'), ['city_id' => $neighbor->id, 'via' => [$unconnected->id]])
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['text'] === 'That route is not available from your current city.');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('a route that loops back through the starting city is refused', function () {
    $user = User::factory()->create();
    $origin = $user->location->city;
    $neighbor = $origin->neighbors()->firstOrFail();

    $this->actingAs($user)
        ->post(route('travel.store'), ['city_id' => $neighbor->id, 'via' => [$neighbor->id, $origin->id]])
        ->assertSessionHas('toast');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});
