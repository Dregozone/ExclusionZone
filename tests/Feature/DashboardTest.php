<?php

use App\Models\CityAction;
use App\Models\Item;
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

test('unavailable city actions say why they cannot be started', function () {
    $user = User::factory()->create();
    $pharmacyRaid = CityAction::query()->where('action_key', 'pharmacy_raid')->firstOrFail();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['data-test="action-pharmacy_raid"', 'Locked'], false)
        ->assertSeeText("Needs Scavenging level {$pharmacyRaid->min_level}. You are level 1.");
});

test('city action cards name the item they reward rather than its key', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('reward Scrap Metal')
        ->assertDontSee('reward scrap_metal');
});

test('a survivor recovering from death sees the countdown and why work is locked', function () {
    $user = User::factory()->create(['recovering_until' => now()->addMinutes(7)]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="recovery-countdown"', false)
        ->assertSeeText('Recovering after your collapse')
        ->assertSeeInOrder(['data-test="action-district_patrols"', 'Recovering'], false)
        ->assertSee('data-test="action-reason-district_patrols"', false);
});

test('the stop button is shown in the condition bar and on the active timer while working', function () {
    $user = User::factory()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $patrols->id]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="hud-stop-work"', false)
        ->assertSeeInOrder(['data-test="cancel-work"', 'Stop working'], false);
});

test('eating from the city menu in the background answers with json instead of a redirect', function () {
    $user = User::factory()->create(['hunger' => 50]);
    $borscht = Item::query()->where('key', 'borscht')->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $borscht->id, 'quantity' => 2]);

    $this->actingAs($user)
        ->postJson(route('food.eat'), ['item_id' => $borscht->id])
        ->assertOk()
        ->assertJsonPath('status', fn (string $status): bool => str_starts_with($status, 'You ate 1x Borscht'));

    $this->actingAs($user)
        ->postJson(route('food.eat'), ['item_id' => Item::query()->where('key', 'scrap_metal')->value('id')])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Scrap Metal is not something you can eat.']);
});

test('the PDA has no map or travel tabs and shows the pack as pictures', function () {
    $user = User::factory()->create();
    $borscht = Item::query()->where('key', 'borscht')->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $borscht->id, 'quantity' => 4]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('data-test="pda-tab-map"', false)
        ->assertDontSee('data-test="pda-tab-travel"', false)
        ->assertSee('data-test="pda-tab-gear"', false)
        ->assertSee('data-test="pda-tab-bazaar"', false)
        ->assertSee('data-test="pack-item-borscht"', false)
        ->assertSee('images/items/borscht.jpg', false)
        ->assertDontSee('data-test="survivor-condition"', false);
});

test('items without a picture of their own show the plain crate', function () {
    $user = User::factory()->create();
    $newItem = Item::factory()->create(['key' => 'admin_made_widget']);
    $user->inventoryItems()->create(['item_id' => $newItem->id, 'quantity' => 1]);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('images/items/default.jpg', false)
        ->assertDontSee('images/items/admin_made_widget.jpg', false);
});
