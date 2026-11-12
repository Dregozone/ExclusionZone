<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the survivor HUD shows who you are, where you are and your condition', function () {
    $user = User::factory()->create(['hunger' => 80]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder([
            'data-test="survivor-hud"',
            $user->name,
            $user->location->city->city,
            'Hunger',
            '80',
            'data-test="location-hero"',
        ], false);
});
