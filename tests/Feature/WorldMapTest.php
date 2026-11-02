<?php

use App\Models\User;

test('the world map page renders the interactive map for guests', function () {
    $this->get(route('world-map'))
        ->assertOk()
        ->assertSee('World Map')
        ->assertSee('data-geo-map', false)
        ->assertSee('Find a city or country')
        ->assertDontSee('x-ref="travelForm"', false);
});

test('signed-in players get a travel form on the world map', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('world-map'))
        ->assertOk()
        ->assertSee('x-ref="travelForm"', false)
        ->assertSee(route('travel.store'), false);
});
