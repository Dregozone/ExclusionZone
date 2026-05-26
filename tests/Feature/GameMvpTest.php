<?php

use App\Livewire\Admin\ChangeUserRole as ChangeUserRolePage;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Country;
use App\Models\PremiumCosmetic;
use App\Models\Role;
use App\Models\RoleChangeAudit;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserMute;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

test('landing page is available from the home route', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Exclusion Zone')
        ->assertSee('Register');
});

test('newly registered users receive a city menu, starting location, skills, and profile data', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Scout',
        'email' => 'scout@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'scout@example.com')->firstOrFail();

    expect($user->location)->not->toBeNull();
    expect($user->skills)->toHaveCount(13);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('City actions')
        ->assertSee($user->location->city->city);
});

test('city actions are filtered by the current location and movement refreshes the city menu', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('dashboard'))
        ->assertSee('District patrols')
        ->assertDontSee('Harbor fishing');

    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();

    $this->post(route('travel.store'), ['city_id' => $warsaw->id])
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))
        ->assertSee('Defensive wall building')
        ->assertDontSee('District patrols');
});

test('performing a city action grants experience and adds items to inventory', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $this->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'));

    $user->refresh()->load('skills.skill', 'inventoryItems.item');

    expect($user->skillFor('scavenging')?->xp)->toBe(18);
    expect($user->inventoryItems->firstWhere('item.key', 'scrap_metal')?->quantity)->toBe(2);
});

test('users without city action permissions get a danger toast instead of a 403 page', function () {
    $user = User::factory()->asRole('guest')->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['heading'] === 'Action unavailable'
            && $toast['text'] === 'Your current role cannot travel or perform city actions.'
            && $toast['variant'] === 'danger');
});

test('wrong-city action attempts redirect back with a danger toast', function () {
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'defensive_wall_building')->firstOrFail();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['heading'] === 'Action unavailable'
            && $toast['text'] === 'You can only perform actions in your current city.'
            && $toast['variant'] === 'danger');
});

test('feature hook denials redirect back with a danger toast instead of a 403 page', function () {
    $user = User::factory()->asRole('guest')->create();

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('feature-hook.store', 'trade'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['heading'] === 'Action unavailable'
            && $toast['text'] === 'Your current role cannot access the trade board.'
            && $toast['variant'] === 'danger');
});

test('skill thresholds deny and allow access at the documented boundary', function () {
    $user = User::factory()->create();
    $manaus = City::query()->where('city', 'Manaus')->firstOrFail();
    $action = CityAction::query()->where('action_key', 'jungle_hunt')->firstOrFail();

    $user->location()->update([
        'country_id' => $manaus->country_id,
        'city_id' => $manaus->id,
    ]);

    $hunting = $user->skillFor('hunting');
    $hunting->update(['level' => 11, 'xp' => 0]);

    $this->actingAs($user)
        ->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect();

    expect($user->fresh()->inventoryItems()->count())->toBe(0);

    $hunting->refresh()->update(['level' => 12, 'xp' => 0]);

    $this->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->inventoryItems()->exists())->toBeTrue();
});

test('premium cosmetic equip succeeds and does not change gameplay rewards', function () {
    $regular = User::factory()->create();
    $premium = User::factory()->premium()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $cosmetic = PremiumCosmetic::query()->where('name', 'Wasteland Ranger Set')->firstOrFail();

    $this->actingAs($premium)
        ->post(route('cosmetics.store'), ['premium_cosmetic_id' => $cosmetic->id])
        ->assertRedirect(route('dashboard'));

    expect($premium->fresh()->cosmeticLoadout?->outfit_skin_id)->toBe($cosmetic->id);

    $this->actingAs($regular)->post(route('city-action.store'), ['city_action_id' => $action->id]);
    $this->actingAs($premium)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $regular->refresh()->load('inventoryItems.item');
    $premium->refresh()->load('inventoryItems.item');

    expect($regular->skillFor('scavenging')?->xp)->toBe($premium->skillFor('scavenging')?->xp)
        ->and($regular->inventoryItems->firstWhere('item.key', 'scrap_metal')?->quantity)
        ->toBe($premium->inventoryItems->firstWhere('item.key', 'scrap_metal')?->quantity);
});

test('admin can change another user role and non admins cannot', function () {
    $admin = User::factory()->asRole('admin')->create();
    $moderator = User::factory()->asRole('moderator')->create();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.roles.update'), [
            'target_user_id' => $target->id,
            'role_key' => 'moderator',
        ])->assertRedirect(route('dashboard'));

    expect($target->fresh()->role?->key)->toBe('moderator');
    expect(RoleChangeAudit::query()->count())->toBe(1);

    $secondTarget = User::factory()->create();

    $this->actingAs($moderator)
        ->post(route('admin.roles.update'), [
            'target_user_id' => $secondTarget->id,
            'role_key' => 'admin',
        ])->assertRedirect();

    expect($secondTarget->fresh()->role?->key)->toBe('user');
    expect(RoleChangeAudit::query()->count())->toBe(1);
});

test('authenticated users can reach the change user role page from navigation', function () {
    $user = User::factory()->asRole('guest')->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Change User Role')
        ->assertSee(route('admin.change-user-role', absolute: false));

    $this->get(route('admin.change-user-role'))
        ->assertOk()
        ->assertSee('Current Role')
        ->assertSee('Selected Role Preview');
});

test('livewire change user role page shows permissions and updates roles without admin restrictions', function () {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $target->load('role.tasks');

    $currentRole = $target->role;
    $newRole = Role::query()->with('tasks')->where('key', 'moderator')->firstOrFail();
    $currentPermission = $currentRole?->tasks->sortBy('description')->first()?->description;
    $newPermission = $newRole->tasks->sortBy('description')->first()?->description;

    expect($currentRole)->not->toBeNull()
        ->and($currentPermission)->not->toBeNull()
        ->and($newPermission)->not->toBeNull();

    $this->actingAs($actor);

    Livewire::test(ChangeUserRolePage::class)
        ->set('selectedUserId', $target->id)
        ->assertSee($currentRole->name)
        ->assertSee($currentPermission)
        ->set('selectedRoleId', $newRole->id)
        ->assertSee($newRole->name)
        ->assertSee($newPermission)
        ->call('changeRole')
        ->assertHasNoErrors();

    expect($target->fresh()->role?->key)->toBe('moderator')
        ->and(RoleChangeAudit::query()
            ->where('actor_user_id', $actor->id)
            ->where('target_user_id', $target->id)
            ->where('new_role_id', $newRole->id)
            ->exists())
        ->toBeTrue();
});

test('moderators can temporarily mute users and the mute expires', function () {
    Carbon::setTestNow('2026-05-24 12:00:00');

    $moderator = User::factory()->asRole('moderator')->create();
    $target = User::factory()->create();

    $this->actingAs($moderator)
        ->post(route('moderation.mutes.store'), [
            'target_user_id' => $target->id,
            'duration_minutes' => 60,
            'reason' => 'Spam',
        ])->assertRedirect(route('dashboard'));

    expect(UserMute::query()->count())->toBe(1)
        ->and($target->fresh()->isMuted())->toBeTrue();

    Carbon::setTestNow(now()->addMinutes(61));

    expect($target->fresh()->isMuted())->toBeFalse();

    Carbon::setTestNow();
});

test('reference data from the specification is seeded and bounded', function () {
    expect(Role::query()->count())->toBe(5)
        ->and(Skill::query()->count())->toBe(13)
        ->and(Country::query()->count())->toBe(6)
        ->and(City::query()->count())->toBe(12)
        ->and(Country::query()->whereNotBetween('rain_chance_pct', [0, 100])->count())->toBe(0)
        ->and(City::query()->whereNotBetween('trouble_chance_pct', [0, 100])->count())->toBe(0);
});
