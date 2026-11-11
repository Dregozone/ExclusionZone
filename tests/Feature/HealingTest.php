<?php

use App\Livewire\Admin\ManageCityActions;
use App\Livewire\Admin\ManageItems;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use App\Weather;
use Livewire\Livewire;

/**
 * A survivor standing in Kyiv in clear weather, so only their condition changes timers.
 */
function hurtSurvivor(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->location->city->update(['weather' => Weather::Clear]);

    return $user->fresh();
}

function packItem(User $user, string $itemKey, int $quantity): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);

    return $item;
}

function metroFieldClinic(): CityAction
{
    $action = CityAction::query()->where('action_key', 'metro_field_clinic')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'hunger_cost' => 0, 'fatigue_cost' => 0]);

    return $action->fresh();
}

// ── Medical items ────────────────────────────────────────────────────────────

test('using a bandage uses one and restores its health', function () {
    $user = hurtSurvivor(['health' => 40, 'rested_at' => now()]);
    $bandages = packItem($user, 'bandages', 2);

    $this->actingAs($user)->post(route('medical.use'), ['item_id' => $bandages->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'You used 1x Bandages (health +15).');

    expect($user->fresh()->health)->toBe(55);
    $this->assertDatabaseHas('inventory_items', ['user_id' => $user->id, 'item_id' => $bandages->id, 'quantity' => 1]);
});

test('a medkit heals no further than full health and the last one leaves the pack', function () {
    $user = hurtSurvivor(['health' => 80, 'rested_at' => now()]);
    $medkits = packItem($user, 'field_medkits', 1);

    $this->actingAs($user)->post(route('medical.use'), ['item_id' => $medkits->id])
        ->assertSessionHas('status', 'You used 1x Field Medkits (health +20).');

    expect($user->fresh()->health)->toBe(100);
    $this->assertDatabaseMissing('inventory_items', ['user_id' => $user->id, 'item_id' => $medkits->id]);
});

test('survivors can patch themselves up while work is running', function () {
    $user = hurtSurvivor(['health' => 40]);
    $antiseptic = packItem($user, 'antiseptic', 1);
    $this->actingAs($user)->post(route('city-action.store'), [
        'city_action_id' => CityAction::query()->where('action_key', 'district_patrols')->value('id'),
    ]);

    $this->actingAs($user)->post(route('medical.use'), ['item_id' => $antiseptic->id]);

    expect($user->fresh()->health)->toBe(65);
});

test('using a medical item is refused without changing anything', function (string $itemKey, int $carried, int $health, string $message) {
    $user = hurtSurvivor(['health' => $health, 'rested_at' => now()]);
    $item = Item::query()->where('key', $itemKey)->firstOrFail();

    if ($carried > 0) {
        $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $carried]);
    }

    $this->actingAs($user)->post(route('medical.use'), ['item_id' => $item->id])
        ->assertSessionHas('toast.text', $message);

    expect($user->fresh()->health)->toBe($health);
    expect((int) $user->inventoryItems()->where('item_id', $item->id)->value('quantity'))->toBe($carried);
})->with([
    'not medical' => ['borscht', 1, 40, 'Borscht will not heal you.'],
    'not carried' => ['bandages', 0, 40, 'You are not carrying any Bandages.'],
    'not hurt' => ['bandages', 1, 100, 'You are not hurt. Save it for later.'],
]);

test('medical items cannot be eaten', function () {
    $user = hurtSurvivor(['health' => 40, 'hunger' => 60]);
    $bandages = packItem($user, 'bandages', 1);

    $this->actingAs($user)->post(route('food.eat'), ['item_id' => $bandages->id])
        ->assertSessionHas('toast.text', 'Bandages is not something you can eat.');

    expect($user->fresh()->health)->toBe(40);
});

test('using a medical item needs an item', function () {
    $this->actingAs(hurtSurvivor())->post(route('medical.use'), [])
        ->assertSessionHasErrors(['item_id' => 'The item id field is required.']);
});

test('guests are sent to log in before using a medical item', function () {
    $this->post(route('medical.use'), ['item_id' => 1])->assertRedirect(route('login'));
});

// ── Crafting medical items ───────────────────────────────────────────────────

test('rolling bandages in warsaw turns clean water into bandages', function () {
    $user = hurtSurvivor();
    $user->location->update(['city_id' => City::query()->where('city', 'Warsaw')->value('id')]);
    packItem($user, 'clean_water', 2);
    $action = CityAction::query()->where('action_key', 'bandage_rolling')->firstOrFail();
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->travel(1)->hours();
    $this->artisan('work:advance-loops');

    $bandages = Item::query()->where('key', 'bandages')->firstOrFail();
    $this->assertDatabaseHas('inventory_items', ['user_id' => $user->id, 'item_id' => $bandages->id, 'quantity' => 2]);
    expect($user->inventoryItems()->count())->toBe(1);
});

// ── Field clinics ────────────────────────────────────────────────────────────

test('each field clinic cycle uses its fee and restores health', function () {
    $user = hurtSurvivor(['health' => 50]);
    packItem($user, 'ration_tokens', 10);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => metroFieldClinic()->id]);

    $this->travel(111)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh())
        ->health->toBe(65)
        ->activeWork->last_cycle_summary->toBe('Action completed: used 2x Ration Tokens, +6 XP, +15 health.');
    $this->assertDatabaseHas('inventory_items', ['user_id' => $user->id, 'quantity' => 8]);
});

test('the field clinic stops once the survivor is fully healed', function () {
    $user = hurtSurvivor(['health' => 80]);
    packItem($user, 'ration_tokens', 10);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => metroFieldClinic()->id]);

    $this->travel(1)->hours();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh())
        ->health->toBe(100)
        ->activeWork->toBeNull()
        ->last_work_report->toContain('You are not hurt, so there is nothing to patch up.');
    $this->assertDatabaseHas('inventory_items', ['user_id' => $user->id, 'quantity' => 6]);
});

test('survivors at full health cannot start a field clinic', function () {
    $user = hurtSurvivor();
    packItem($user, 'ration_tokens', 10);

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => metroFieldClinic()->id])
        ->assertSessionHas('toast.text', 'You are not hurt, so there is nothing to patch up.');

    expect($user->fresh()->activeWork)->toBeNull();
});

// ── Resting ──────────────────────────────────────────────────────────────────

test('health comes back for every whole minute spent resting unless starving', function (int $hunger, int $expectedHealth) {
    config(['survival.rest.health_recovered_per_minute' => 2]);
    $user = hurtSurvivor(['health' => 40, 'hunger' => $hunger, 'rested_at' => now()]);

    $this->travel(10)->minutes();
    $this->travel(30)->seconds();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()->health)->toBe($expectedHealth);
})->with([
    'fed' => [99, 60],
    'starving' => [100, 40],
]);

test('health does not come back while work is running', function () {
    $user = hurtSurvivor(['health' => 40, 'rested_at' => now()]);
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 3600]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->travel(30)->minutes();
    $this->actingAs($user)->get(route('dashboard'))->assertOk();

    expect($user->fresh()->health)->toBe(40);
});

// ── Dashboard ────────────────────────────────────────────────────────────────

test('the dashboard offers the medical items a survivor carries and the clinic', function () {
    $user = hurtSurvivor(['health' => 40, 'rested_at' => now()]);
    $bandages = packItem($user, 'bandages', 3);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="use-medical-'.$bandages->id.'"', false)
        ->assertSeeText('Health +15')
        ->assertSeeText('Metro field clinic')
        ->assertSeeText('reward health +15');
});

// ── Admin configuration ──────────────────────────────────────────────────────

test('admins mark an item as medical', function () {
    $admin = User::factory()->admin()->create();
    $item = Item::query()->where('key', 'scrap_metal')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageItems::class)
        ->call('edit', $item->id)
        ->set('is_medical', true)
        ->set('health_restored', 10)
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh()->isMedical())->toBeTrue();
});

test('admins set how much health a city action restores, without an item reward', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('reward_item_key', '')
        ->set('reward_health', 12)
        ->call('save')
        ->assertHasNoErrors();

    expect($action->fresh()->reward_profile)
        ->toHaveKey('health', 12)
        ->not->toHaveKey('item_key');
});

test('admins must give an action an item reward unless it heals', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->set('reward_item_key', '')
        ->set('reward_health', 0)
        ->call('save')
        ->assertHasErrors(['reward_item_key' => 'required_if']);
});
