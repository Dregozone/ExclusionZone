<?php

use App\Actions\Game\BuildCityMenuData;
use App\Livewire\Admin\ManageCityActions;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function moveUserToCity(User $user, string $cityName): void
{
    $city = City::query()->where('city', $cityName)->firstOrFail();

    $user->location()->update([
        'country_id' => $city->country_id,
        'city_id' => $city->id,
    ]);
}

function giveUserItem(User $user, string $itemKey, int $quantity): void
{
    $user->inventoryItems()->create([
        'item_id' => Item::query()->where('key', $itemKey)->value('id'),
        'quantity' => $quantity,
    ]);
}

function inventoryQuantity(User $user, string $itemKey): ?int
{
    return $user->fresh()->inventoryItems()->whereRelation('item', 'key', $itemKey)->value('quantity');
}

function startRationCooking(User $user): void
{
    $action = CityAction::query()->where('action_key', 'ration_cooking')->firstOrFail();

    test()->actingAs($user)
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'));
}

afterEach(function () {
    Carbon::setTestNow();
});

test('cooking and crafting actions require raw materials', function () {
    $requiredItems = CityAction::query()
        ->whereIn('action_key', ['ration_cooking', 'fish_processing', 'ammo_crafting', 'armor_crafting', 'weapon_assembly', 'herbal_medicine_crafting'])
        ->pluck('required_items', 'action_key');

    expect($requiredItems->all())->toEqual([
        'ration_cooking' => [['item_key' => 'foraged_produce', 'quantity' => 2]],
        'ammo_crafting' => [['item_key' => 'scrap_metal', 'quantity' => 2]],
        'fish_processing' => [['item_key' => 'fresh_fish', 'quantity' => 2]],
        'weapon_assembly' => [['item_key' => 'metal_parts', 'quantity' => 2]],
        'herbal_medicine_crafting' => [['item_key' => 'rare_herbs', 'quantity' => 1]],
        'armor_crafting' => [['item_key' => 'scrap_metal', 'quantity' => 2]],
    ])->and(Item::query()->where('key', 'fresh_fish')->exists())->toBeTrue();
});

test('starting an action without its required items is denied with a danger toast', function () {
    $user = User::factory()->create();
    moveUserToCity($user, 'Warsaw');
    giveUserItem($user, 'foraged_produce', 1);
    $action = CityAction::query()->where('action_key', 'ration_cooking')->firstOrFail();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['text'] === 'You need 2x Foraged Produce to do that.'
            && $toast['variant'] === 'danger');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('required items stay in the inventory until the work completes and are then used up', function () {
    $user = User::factory()->create();
    moveUserToCity($user, 'Warsaw');
    giveUserItem($user, 'foraged_produce', 2);

    startRationCooking($user);

    expect(inventoryQuantity($user, 'foraged_produce'))->toBe(2);

    Carbon::setTestNow(now()->addSeconds($user->fresh('activeWork')->activeWork->duration_seconds + 1));

    $this->post(route('work.complete'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Action completed: used 2x Foraged Produce, +15 XP, +2 Sealed Rations.');

    expect(inventoryQuantity($user, 'foraged_produce'))->toBeNull()
        ->and(inventoryQuantity($user, 'sealed_rations'))->toBe(2);
});

test('only the required quantity is used when the player carries more', function () {
    $user = User::factory()->create();
    moveUserToCity($user, 'Warsaw');
    giveUserItem($user, 'foraged_produce', 5);

    startRationCooking($user);

    Carbon::setTestNow(now()->addSeconds($user->fresh('activeWork')->activeWork->duration_seconds + 1));

    $this->post(route('work.complete'))->assertRedirect(route('dashboard'));

    expect(inventoryQuantity($user, 'foraged_produce'))->toBe(3);
});

test('completing fails without rewards when the required items are gone by the end of the timer', function () {
    $user = User::factory()->create();
    moveUserToCity($user, 'Warsaw');
    giveUserItem($user, 'foraged_produce', 2);

    startRationCooking($user);

    $user->inventoryItems()->delete();
    Carbon::setTestNow(now()->addSeconds($user->fresh('activeWork')->activeWork->duration_seconds + 1));

    $this->post(route('work.complete'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Action failed: you no longer have 2x Foraged Produce. No rewards were granted.');

    expect(inventoryQuantity($user, 'sealed_rations'))->toBeNull()
        ->and($user->fresh()->skillFor('cooking')?->xp ?? 0)->toBe(0);
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('the city menu marks actions unavailable while required items are missing', function () {
    $user = User::factory()->create();
    moveUserToCity($user, 'Warsaw');
    giveUserItem($user, 'foraged_produce', 1);

    $rationCooking = (new BuildCityMenuData)($user)['actions']
        ->first(fn (array $entry): bool => $entry['action']->action_key === 'ration_cooking');

    expect($rationCooking['available'])->toBeFalse()
        ->and($rationCooking['has_required_items'])->toBeFalse()
        ->and($rationCooking['required_items'][0]['item']->key)->toBe('foraged_produce')
        ->and($rationCooking['required_items'][0]['quantity'])->toBe(2)
        ->and($rationCooking['required_items'][0]['owned'])->toBe(1);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Foraged Produce ×2 (1 carried)')
        ->assertSee('Missing items');
});

test('admin can set the items an action requires', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->call('addRequiredItem')
        ->set('required_items.0.item_key', 'scrap_metal')
        ->set('required_items.0.quantity', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect($action->fresh()->required_items)->toBe([['item_key' => 'scrap_metal', 'quantity' => 3]]);
});

test('admin clearing required items stores none', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'ration_cooking')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->assertSet('required_items', [['item_key' => 'foraged_produce', 'quantity' => 2]])
        ->call('removeRequiredItem', 0)
        ->call('save')
        ->assertHasNoErrors();

    expect($action->fresh()->required_items)->toBeNull();
});

test('admin cannot require an item that does not exist', function () {
    $admin = User::factory()->admin()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $action->id)
        ->call('addRequiredItem')
        ->set('required_items.0.item_key', 'unobtainium')
        ->call('save')
        ->assertHasErrors(['required_items.0.item_key' => 'exists']);
});
