<?php

use App\Models\User;

test('guests see the landing link in the sidebar', function () {
    $this->get(route('high-scores'))
        ->assertOk()
        ->assertSee(route('landing'), false);
});

test('signed-in survivors do not see the landing link in the sidebar', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('high-scores'))
        ->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertDontSee(route('landing'), false);
});
