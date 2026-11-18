<?php

use App\EquipSlot;
use App\Livewire\Admin\ManageCityActions;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/**
 * A survivor in Kyiv in clear weather, where district patrols can be gated behind a crowbar in hand.
 */
function patrolSurvivor(): User
{
    $user = User::factory()->create();
    $user->location->city->update(['weather' => Weather::Clear]);

    return $user->fresh();
}

function crowbarPatrols(): CityAction
{
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['required_gear' => ['crowbar'], 'hazard_chance_pct' => 0]);

    return $action->fresh();
}

function putOn(User $user, string $itemKey, EquipSlot $slot): void
{
    $user->equipment()->create(['slot' => $slot, 'item_id' => Item::query()->where('key', $itemKey)->value('id')]);
}

afterEach(function () {
    Carbon::setTestNow();
});

test('an action needing gear is refused until every piece is worn', function (array $worn, string $message) {
    $user = User::factory()->create();
    $pripyat = City::query()->where('city', 'Pripyat')->firstOrFail();
    $user->location()->update(['country_id' => $pripyat->country_id, 'city_id' => $pripyat->id]);
    $user->skillFor('scavenging')->update(['level' => 15]);
    foreach ($worn as $itemKey => $slot) {
        putOn($user, $itemKey, $slot);
    }
    $reactorZone = CityAction::query()->where('action_key', 'scavenge_reactor_zone')->firstOrFail();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('city-action.store'), ['city_action_id' => $reactorZone->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['text'] === $message && $toast['variant'] === 'danger');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
})->with([
    'nothing worn' => [[], 'You need to wear Gas Mask and Hazmat Suit to do that.'],
    'only the mask' => [['gas_mask' => EquipSlot::Head], 'You need to wear Hazmat Suit to do that.'],
]);

test('wearing the gear lets the action start without using the gear up', function () {
    $user = patrolSurvivor();
    $patrols = crowbarPatrols();
    putOn($user, 'crowbar', EquipSlot::RightArm);

    $this->actingAs($user)
        ->post(route('city-action.store'), ['city_action_id' => $patrols->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('user_works', ['user_id' => $user->id, 'city_action_id' => $patrols->id]);
    $this->assertDatabaseHas('user_equipment', ['user_id' => $user->id, 'slot' => EquipSlot::RightArm->value]);
});

test('taking the gear off stops the work loop at the next cycle', function () {
    $user = patrolSurvivor();
    $patrols = crowbarPatrols();
    putOn($user, 'crowbar', EquipSlot::RightArm);
    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $patrols->id]);
    $user->equipment()->delete();

    Carbon::setTestNow(now()->addSeconds($user->fresh('activeWork')->activeWork->duration_seconds + 1));
    $this->get(route('dashboard'))->assertOk();

    expect($user->fresh()->last_work_report)->toEndWith('You need to wear Crowbar to do that.');
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('the city menu says which gear an action needs and whether it is worn', function () {
    $user = patrolSurvivor();
    crowbarPatrols();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Crowbar (not worn)')
        ->assertSee('Needs gear')
        ->assertSee('Wear Crowbar first. Gear is not used up.');
});

test('admins choose the gear an action needs', function () {
    $admin = User::factory()->admin()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $patrols->id)
        ->set('required_gear', ['gas_mask', 'crowbar'])
        ->call('save')
        ->assertHasNoErrors();

    expect($patrols->fresh()->required_gear)->toBe(['gas_mask', 'crowbar']);
});

test('admins clearing the gear an action needs store none', function () {
    $admin = User::factory()->admin()->create();
    $patrols = crowbarPatrols();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $patrols->id)
        ->assertSet('required_gear', ['crowbar'])
        ->set('required_gear', [])
        ->call('save')
        ->assertHasNoErrors();

    expect($patrols->fresh()->required_gear)->toBeNull();
});

test('admins cannot require gear that cannot be worn', function () {
    $admin = User::factory()->admin()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageCityActions::class)
        ->call('edit', $patrols->id)
        ->set('required_gear', ['scrap_metal'])
        ->call('save')
        ->assertHasErrors(['required_gear.0' => 'exists']);

    expect($patrols->fresh()->required_gear)->toBeNull();
});
