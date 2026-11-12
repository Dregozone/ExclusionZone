<?php

use App\Models\User;

test('appearance settings offer reduce effects and high contrast instead of a light theme', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertSee('data-test="reduce-effects-toggle"', false)
        ->assertSee('data-test="high-contrast-toggle"', false)
        ->assertDontSee('value="light"', false);
});
