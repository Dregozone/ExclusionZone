<?php

use App\Actions\Game\CalculateWorkDuration;
use App\Livewire\Admin\ManageCityActions;
use App\Livewire\Admin\ManageItems;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use App\Weather;
use Livewire\Livewire;

/**
 * A survivor standing in Kyiv in clear weather, so only hunger and fatigue change timers.
 */
function survivorInClearKyiv(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->location->city->update(['weather' => Weather::Clear]);

    return $user->fresh();
}

function districtPatrols(): CityAction
{
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'hunger_cost' => 0.6, 'fatigue_cost' => 0.9]);

    return $action->fresh();
}

function giveItem(User $user, string $itemKey, int $quantity): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);

    return $item;
}

// ── Gaining hunger and fatigue ───────────────────────────────────────────────

test('each finished work cycle adds the action hunger and fatigue cost', function () {
    $user = survivorInClearKyiv();
    $action = districtPatrols();
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->travel(201)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh())
        ->hunger->toBe(1.2)
        ->fatigue->toBe(1.8);
});

test('arriving at the end of a travel leg adds the configured travel cost', function () {
    config(['survival.travel.hunger_per_leg' => 0.7, 'survival.travel.fatigue_per_leg' => 0.8]);
    $user = survivorInClearKyiv();
    $destination = $user->location->city->neighbors()->firstOrFail();
    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);

    $this->travel(1)->days();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh())
        ->hunger->toBe(0.7)
        ->fatigue->toBe(0.8);
});

test('hunger and fatigue never rise above the maximum', function () {
    $user = survivorInClearKyiv(['hunger' => 99.7, 'fatigue' => 99.5]);
    $action = districtPatrols();
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->travel(1)->hours();
    $this->artisan('work:advance-loops');

    expect($user->fresh())
        ->hunger->toBe(100.0)
        ->fatigue->toBe(100.0);
});

// ── Slower timers ────────────────────────────────────────────────────────────

test('work timers grow with the highest hunger and fatigue threshold reached', function (int $hunger, int $fatigue, int $expectedSeconds) {
    $user = survivorInClearKyiv(['hunger' => $hunger, 'fatigue' => $fatigue]);

    $duration = app(CalculateWorkDuration::class)->forCityAction($user, districtPatrols());

    expect($duration)->toBe($expectedSeconds);
})->with([
    'fed and rested' => [49, 49, 100],
    'hungry' => [50, 0, 110],
    'famished and tired' => [80, 60, 135],
    'starving and spent' => [100, 100, 200],
]);

test('a starving survivor can still start work, it just takes longer', function () {
    $user = survivorInClearKyiv(['hunger' => 100, 'fatigue' => 0]);
    $action = districtPatrols();

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh('activeWork')->activeWork->duration_seconds)->toBe(150);
});

// ── Resting ──────────────────────────────────────────────────────────────────

test('fatigue falls for every whole minute spent without work', function () {
    config(['survival.rest.fatigue_recovered_per_minute' => 2]);
    $user = survivorInClearKyiv(['hunger' => 40, 'fatigue' => 30.5, 'rested_at' => now()]);

    $this->travel(10)->minutes();
    $this->travel(30)->seconds();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh())
        ->fatigue->toBe(10.5)
        ->hunger->toBe(40.0);
});

test('fatigue does not fall while work is running', function () {
    $user = survivorInClearKyiv(['fatigue' => 30, 'rested_at' => now()]);
    $action = districtPatrols();
    $action->update(['base_duration_seconds' => 3600]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->travel(30)->minutes();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()->fatigue)->toBe(30.0);
});

test('stopping work starts the rest from that moment', function () {
    $user = survivorInClearKyiv(['fatigue' => 30, 'rested_at' => now()]);
    $action = districtPatrols();
    $action->update(['base_duration_seconds' => 3600]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);
    $this->travel(30)->minutes();
    $this->actingAs($user)->post(route('work.cancel'));

    $this->travel(5)->minutes();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()->fatigue)->toBe(25.0);
});

// ── Eating ───────────────────────────────────────────────────────────────────

test('eating borscht uses one bowl and eases hunger and fatigue', function () {
    $user = survivorInClearKyiv(['hunger' => 60, 'fatigue' => 3]);
    $borscht = giveItem($user, 'borscht', 2);

    $this->actingAs($user)->post(route('food.eat'), ['item_id' => $borscht->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'You ate 1x Borscht (hunger -45, fatigue -3).');

    expect($user->fresh())
        ->hunger->toBe(15.0)
        ->fatigue->toBe(0.0);
    $this->assertDatabaseHas('inventory_items', ['user_id' => $user->id, 'item_id' => $borscht->id, 'quantity' => 1]);
});

test('eating the last vodka fish removes it from the pack', function () {
    $user = survivorInClearKyiv(['hunger' => 30, 'fatigue' => 30]);
    $vodkaFish = giveItem($user, 'vodka_fish', 1);

    $this->actingAs($user)->post(route('food.eat'), ['item_id' => $vodkaFish->id]);

    expect($user->fresh())
        ->hunger->toBe(5.0)
        ->fatigue->toBe(10.0);
    $this->assertDatabaseMissing('inventory_items', ['user_id' => $user->id, 'item_id' => $vodkaFish->id]);
});

test('survivors can eat while work is running', function () {
    $user = survivorInClearKyiv(['hunger' => 60]);
    $borscht = giveItem($user, 'borscht', 1);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => districtPatrols()->id]);

    $this->actingAs($user)->post(route('food.eat'), ['item_id' => $borscht->id]);

    expect($user->fresh()->hunger)->toBe(15.0);
});

test('eating is refused without changing anything', function (string $itemKey, int $carried, float $hunger, string $message) {
    $user = survivorInClearKyiv(['hunger' => $hunger]);
    $item = Item::query()->where('key', $itemKey)->firstOrFail();

    if ($carried > 0) {
        $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $carried]);
    }

    $this->actingAs($user)->post(route('food.eat'), ['item_id' => $item->id])
        ->assertSessionHas('toast.text', $message);

    expect($user->fresh()->hunger)->toBe($hunger);
    expect((int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity'))->toBe($carried);
})->with([
    'not food' => ['scrap_metal', 3, 60.0, 'Scrap Metal is not something you can eat.'],
    'not carried' => ['borscht', 0, 60.0, 'You are not carrying any Borscht.'],
    'not hungry, tired or hurt' => ['borscht', 1, 0.0, 'You are not hungry, tired or hurt. Save it for later.'],
]);

test('eating needs an item', function () {
    $user = survivorInClearKyiv();

    $this->actingAs($user)->post(route('food.eat'), [])
        ->assertSessionHasErrors(['item_id' => 'The item id field is required.']);
});

test('guests are sent to log in before eating', function () {
    $this->post(route('food.eat'), ['item_id' => 1])->assertRedirect(route('login'));
});

// ── Borscht cooking ──────────────────────────────────────────────────────────

test('cooking borscht in kyiv turns produce and water into borscht', function () {
    $user = survivorInClearKyiv();
    giveItem($user, 'foraged_produce', 2);
    giveItem($user, 'clean_water', 1);
    $action = CityAction::query()->where('action_key', 'borscht_cooking')->firstOrFail();
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->travel(1)->hours();
    $this->artisan('work:advance-loops');

    $borscht = Item::query()->where('key', 'borscht')->firstOrFail();
    $this->assertDatabaseHas('inventory_items', ['user_id' => $user->id, 'item_id' => $borscht->id, 'quantity' => 2]);
    expect($user->inventoryItems()->count())->toBe(1);
});

// ── Dashboard ────────────────────────────────────────────────────────────────

test('the dashboard shows the survivor condition and the food they can eat', function () {
    $user = survivorInClearKyiv(['hunger' => 80.5, 'fatigue' => 10, 'rested_at' => now()]);
    $borscht = giveItem($user, 'borscht', 3);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSeeText('Famished · 80.5 / 100')
        ->assertSeeText('Rested · 10 / 100')
        ->assertSeeText('Timers +25%')
        ->assertSee('data-test="eat-'.$borscht->id.'"', false);
});

// ── Admin configuration ──────────────────────────────────────────────────────

test('admins set how much a food item restores', function () {
    $admin = User::factory()->admin()->create();
    $item = Item::query()->where('key', 'scrap_metal')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageItems::class)
        ->call('edit', $item->id)
        ->set('hunger_restored', 12)
        ->set('fatigue_restored', 4)
        ->set('health_restored', 9)
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh())
        ->hunger_restored->toBe(12)
        ->fatigue_restored->toBe(4)
        ->health_restored->toBe(9);
});

test('admins set the hunger and fatigue cost of a city action', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('hunger_cost', 0.7)
        ->set('fatigue_cost', 1.5)
        ->call('save')
        ->assertHasNoErrors();

    expect($action->fresh())
        ->hunger_cost->toBe(0.7)
        ->fatigue_cost->toBe(1.5);
});

test('admins cannot set city action costs finer than a tenth', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('hunger_cost', 0.25)
        ->call('save')
        ->assertHasErrors(['hunger_cost' => 'decimal']);
});

test('every city action in the world costs at most 1 hunger and fatigue per cycle', function () {
    $costs = CityAction::query()->get(['hunger_cost', 'fatigue_cost']);

    expect($costs)->not->toBeEmpty()
        ->and($costs->max('hunger_cost'))->toBeLessThanOrEqual(1.0)
        ->and($costs->max('fatigue_cost'))->toBeLessThanOrEqual(1.0)
        ->and($costs->where('hunger_cost', '>', 0)->min('hunger_cost'))->toBe(0.5);
});

test('admins cannot set values outside 0 to 100', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('hunger_cost', 101)
        ->set('fatigue_cost', -1)
        ->call('save')
        ->assertHasErrors(['hunger_cost' => 'max', 'fatigue_cost' => 'min']);
});
