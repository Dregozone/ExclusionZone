<?php

use App\EquipSlot;
use App\Livewire\Admin\ManageItems;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Lottery;
use Livewire\Livewire;

/**
 * A survivor in Kyiv in clear weather, so only their gear changes timers and costs.
 */
function geared(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->location->city->update(['weather' => Weather::Clear]);

    return $user->fresh();
}

function packGear(User $user, string $itemKey, int $quantity = 1): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);

    return $item;
}

function wear(User $user, string $itemKey, EquipSlot $slot): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->equipment()->create(['slot' => $slot, 'item_id' => $item->id]);

    return $item;
}

function carriedGear(User $user, string $itemKey): int
{
    return (int) $user->inventoryItems()->whereRelation('item', 'key', $itemKey)->value('quantity');
}

function scavengingPatrols(): CityAction
{
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'hunger_cost' => 1, 'fatigue_cost' => 1, 'hazard_chance_pct' => 0]);

    return $action->fresh();
}

// ── Putting gear on and taking it off ────────────────────────────────────────

test('wearing an item moves one out of the pack into the slot it fits', function () {
    $user = geared();
    $mask = packGear($user, 'gas_mask', 2);

    $this->actingAs($user)
        ->postJson(route('gear.equip'), ['item_id' => $mask->id])
        ->assertOk()
        ->assertJson(['status' => 'You put on the Gas Mask (Headwear).']);

    expect(carriedGear($user, 'gas_mask'))->toBe(1)
        ->and($user->equipment()->where('slot', EquipSlot::Head)->value('item_id'))->toBe($mask->id);
});

test('held items fill the right arm first, then the left', function () {
    $user = geared();
    $rod = packGear($user, 'fishing_rod');
    $hammer = packGear($user, 'claw_hammer');

    $this->actingAs($user)->postJson(route('gear.equip'), ['item_id' => $rod->id])->assertOk();
    $this->actingAs($user)->postJson(route('gear.equip'), ['item_id' => $hammer->id])->assertOk();

    $worn = $user->equipment()->pluck('item_id', 'slot');

    expect($worn)->toHaveCount(2)
        ->and($worn['right_arm'])->toBe($rod->id)
        ->and($worn['left_arm'])->toBe($hammer->id);
});

test('wearing an item in a full slot puts the old one back in the pack', function () {
    $user = geared();
    wear($user, 'hard_hat', EquipSlot::Head);
    $mask = packGear($user, 'gas_mask');

    $this->actingAs($user)
        ->postJson(route('gear.equip'), ['item_id' => $mask->id, 'slot' => 'head'])
        ->assertOk()
        ->assertJson(['status' => 'You swapped your Hard Hat for the Gas Mask (Headwear).']);

    expect(carriedGear($user, 'hard_hat'))->toBe(1)
        ->and(carriedGear($user, 'gas_mask'))->toBe(0)
        ->and($user->equipment()->value('item_id'))->toBe($mask->id);
});

test('taking an item off puts it back in the pack', function () {
    $user = geared();
    wear($user, 'wellington_boots', EquipSlot::Feet);

    $this->actingAs($user)
        ->post(route('gear.unequip'), ['slot' => 'feet'])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'You took off the Wellington Boots and packed it away.');

    expect(carriedGear($user, 'wellington_boots'))->toBe(1)
        ->and($user->equipment()->exists())->toBeFalse();
});

test('wearing an item is refused without changing anything', function (string $itemKey, int $carried, ?string $slot, string $message) {
    $user = geared();
    $item = Item::query()->where('key', $itemKey)->firstOrFail();

    if ($carried > 0) {
        packGear($user, $itemKey, $carried);
    }

    $this->actingAs($user)
        ->postJson(route('gear.equip'), ['item_id' => $item->id, 'slot' => $slot])
        ->assertUnprocessable()
        ->assertJson(['message' => $message]);

    expect(carriedGear($user, $itemKey))->toBe($carried)
        ->and($user->equipment()->exists())->toBeFalse();
})->with([
    'not wearable' => ['scrap_metal', 1, null, 'Scrap Metal is not something you can wear.'],
    'not carried' => ['gas_mask', 0, null, 'You are not carrying any Gas Mask.'],
    'wrong slot' => ['gas_mask', 1, 'feet', 'Gas Mask does not fit your Footwear slot.'],
]);

test('taking off an empty slot is refused', function () {
    $this->actingAs(geared())
        ->postJson(route('gear.unequip'), ['slot' => 'body'])
        ->assertUnprocessable()
        ->assertJson(['message' => 'You are not wearing anything in your Body slot.']);
});

test('gear requests need a real slot', function () {
    $this->actingAs(geared())
        ->postJson(route('gear.unequip'), ['slot' => 'tail'])
        ->assertJsonValidationErrors('slot');
});

test('guests are sent to log in before wearing gear', function () {
    $this->post(route('gear.equip'), ['item_id' => 1])->assertRedirect(route('login'));
});

// ── What gear does ───────────────────────────────────────────────────────────

test('tools speed up work for their skill only', function () {
    $user = geared();
    wear($user, 'crowbar', EquipSlot::RightArm);

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => scavengingPatrols()->id]);

    expect($user->fresh('activeWork')->activeWork->duration_seconds)->toBe(85);

    $user->activeWork()->delete();
    $repair = CityAction::query()->where('action_key', 'shelter_repair')->firstOrFail();
    $repair->update(['base_duration_seconds' => 100]);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $repair->id]);

    expect($user->fresh('activeWork')->activeWork->duration_seconds)->toBe(100);
});

test('work speed from gear stops at the configured cap', function () {
    config(['equipment.caps.work_speed' => 20]);
    $user = geared();
    wear($user, 'crowbar', EquipSlot::RightArm);
    wear($user, 'scorched_neural_lattice', EquipSlot::Head);
    Item::query()->where('key', 'scorched_neural_lattice')->update(['bonus_skill_key' => 'scavenging']);

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => scavengingPatrols()->id]);

    expect($user->fresh('activeWork')->activeWork->duration_seconds)->toBe(80);
});

test('worn gear eases the hunger and fatigue each work cycle adds', function () {
    $user = geared();
    wear($user, 'emitter_heart', EquipSlot::Body);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => scavengingPatrols()->id]);
    Lottery::alwaysLose();

    $this->travel(100)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh())->hunger->toBe(0.8)->fatigue->toBe(0.8);
});

test('worn gear boosts the health medical items give back', function () {
    $user = geared(['health' => 50]);
    wear($user, 'medic_satchel', EquipSlot::Body);
    $bandages = packGear($user, 'bandages');

    $this->actingAs($user)->postJson(route('medical.use'), ['item_id' => $bandages->id])
        ->assertOk()
        ->assertJson(['status' => 'You used 1x Bandages (health +18).']);

    expect($user->fresh()->health)->toBe(68);
});

test('a gas mask turns aside part of the radiation damage', function () {
    $user = geared();
    $user->location->city->update(['radiation_damage' => 10]);
    wear($user, 'gas_mask', EquipSlot::Head);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => scavengingPatrols()->id]);
    Lottery::alwaysLose();

    $this->travel(100)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    expect($user->fresh()->health)->toBe(94);
});

test('the city menu shows worn gear and what it does', function () {
    $user = geared();
    wear($user, 'crowbar', EquipSlot::RightArm);
    packGear($user, 'gas_mask');

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="equip-gas_mask-head"', false)
        ->assertSeeText('Scavenging work 15% faster')
        ->assertSeeText('Fighting with your Crowbar (attack +2)')
        ->assertSee('data-test="action-gear-district_patrols"', false);
});

test('admins set where an item is worn and what it does', function () {
    $admin = User::factory()->admin()->create();
    $item = Item::query()->where('key', 'scrap_metal')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageItems::class)
        ->call('edit', $item->id)
        ->set('equip_slot', 'feet')
        ->set('bonus_skill_key', 'fishing')
        ->set('work_speed_bonus_percent', 12)
        ->set('fatigue_reduction_percent', 5)
        ->set('radiation_protection_percent', 7)
        ->set('base_price', 9)
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh())
        ->equip_slot->toBe('feet')
        ->bonus_skill_key->toBe('fishing')
        ->work_speed_bonus_percent->toBe(12)
        ->fatigue_reduction_percent->toBe(5)
        ->radiation_protection_percent->toBe(7)
        ->base_price->toBe(9);
});
