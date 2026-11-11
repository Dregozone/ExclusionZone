<?php

use App\Events\SurvivorDied;
use App\HarmCause;
use App\Livewire\Admin\ManageEnemies;
use App\Livewire\Admin\ManageItems;
use App\Models\CityAction;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Lottery;
use Livewire\Livewire;

/**
 * A survivor in Kyiv in clear weather, with fights whose damage rolls are fixed: the survivor hits for
 * 4 plus their skill level, nothing is blocked, and the Kyiv looter has 10 health and hits for 4.
 */
function fighter(array $attributes = [], int $meleeLevel = 1): User
{
    config([
        'combat.survivor.base_damage_min' => 4,
        'combat.survivor.base_damage_max' => 4,
        'combat.survivor.damage_per_skill_level' => 1,
        'combat.survivor.blocked_percent_per_skill_level' => 0,
        'combat.flee_at_health' => 20,
    ]);

    $user = User::factory()->create($attributes);
    $user->location->city->update(['weather' => Weather::Clear]);
    $user->skillFor('combat_melee')->update(['level' => $meleeLevel]);

    return $user->fresh();
}

function looter(array $attributes = []): Enemy
{
    $looter = Enemy::query()->where('key', 'looter')->firstOrFail();
    $looter->update([...['health' => 10, 'min_damage' => 4, 'max_damage' => 4, 'xp_reward' => 12, 'loot' => []], ...$attributes]);

    return $looter->fresh();
}

function ruinClearing(): CityAction
{
    $action = CityAction::query()->where('action_key', 'clear_the_ruins')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'hunger_cost' => 0, 'fatigue_cost' => 0, 'reward_profile' => ['xp' => 5, 'item_key' => 'scrap_metal', 'quantity' => 1]]);

    return $action->fresh();
}

function startFight(User $user): void
{
    test()->actingAs($user)->post(route('city-action.store'), ['city_action_id' => ruinClearing()->id]);
}

/**
 * Let the current round's timer run out and settle it.
 */
function fightRound(User $user): void
{
    $work = $user->fresh('activeWork')->activeWork;
    test()->travelTo($work->available_at->addSecond());
    test()->artisan('work:advance-loops')->assertSuccessful();
}

function fighterCarries(User $user, string $itemKey): int
{
    return (int) $user->inventoryItems()->whereRelation('item', 'key', $itemKey)->value('quantity');
}

// ── Starting a fight ─────────────────────────────────────────────────────────

test('a combat action starts a fight with an enemy in that city testing its skill', function () {
    $looter = looter();
    $user = fighter();

    startFight($user);

    expect($user->fresh('activeWork')->activeWork)
        ->enemy_id->toBe($looter->id)
        ->enemy_health->toBe(10);
});

test('a combat action with no enemy for its skill in the city is ordinary work', function () {
    looter()->delete();
    $user = fighter();

    startFight($user);
    fightRound($user);

    expect($user->fresh('activeWork')->activeWork)
        ->enemy_id->toBeNull()
        ->cycles_completed->toBe(1)
        ->and(fighterCarries($user, 'scrap_metal'))->toBe(1)
        ->and($user->fresh()->health)->toBe(100);
});

test('survivors too hurt to fight cannot start one', function (int $health, bool $allowed) {
    looter();
    $user = fighter(['health' => $health]);

    startFight($user);

    expect($user->fresh('activeWork')->activeWork !== null)->toBe($allowed);
})->with([
    'at the flee threshold' => [20, false],
    'just above it' => [21, true],
]);

// ── Rounds ───────────────────────────────────────────────────────────────────

test('each cycle is one round of blows with no reward until the enemy falls', function () {
    looter();
    $user = fighter();
    startFight($user);

    fightRound($user);

    expect($user->fresh('activeWork')->activeWork)
        ->enemy_health->toBe(5)
        ->cycles_completed->toBe(1)
        ->xp_earned->toBe(0)
        ->last_cycle_summary->toBe('Fighting the Looter: you hit for 5, it hit you for 4. It has 5/10 health left.')
        ->and($user->fresh()->health)->toBe(96)
        ->and(fighterCarries($user, 'scrap_metal'))->toBe(0);
});

test('beating the enemy pays its XP and the action reward, then lines up the next enemy', function () {
    looter();
    $user = fighter();
    startFight($user);

    fightRound($user);
    fightRound($user);

    $work = $user->fresh('activeWork')->activeWork;

    expect($work)
        ->enemy_health->toBe(10)
        ->xp_earned->toBe(17)
        ->last_cycle_summary->toStartWith('You brought down the Looter: you hit for 5 (+12 XP). Action completed: +5 XP, +1 Scrap Metal.')
        ->and($user->fresh()->skillFor('combat_melee')->xp)->toBe(17)
        ->and($user->fresh()->health)->toBe(96);
});

test('each loot table entry drops on its own roll', function (bool $rollsWin, int $expectedPipes) {
    looter(['health' => 5, 'loot' => [['item_key' => 'rusty_pipe', 'chance_percent' => 25, 'quantity' => 2]]]);
    $user = fighter();
    startFight($user);

    Lottery::fix([$rollsWin]);
    fightRound($user);

    expect(fighterCarries($user, 'rusty_pipe'))->toBe($expectedPipes);
})->with([
    'roll wins' => [true, 2],
    'roll loses' => [false, 0],
]);

// ── Odds ─────────────────────────────────────────────────────────────────────

test('higher combat skill hits harder and turns aside more of each blow', function () {
    looter(['health' => 50, 'min_damage' => 10, 'max_damage' => 10]);
    $user = fighter(meleeLevel: 10);
    config(['combat.survivor.blocked_percent_per_skill_level' => 3]);
    startFight($user);

    fightRound($user);

    expect($user->fresh('activeWork')->activeWork->enemy_health)->toBe(36)
        ->and($user->fresh()->health)->toBe(93);
});

test('blocking stops at the configured maximum', function () {
    looter(['health' => 50, 'min_damage' => 10, 'max_damage' => 10]);
    $user = fighter(meleeLevel: 30);
    config(['combat.survivor.blocked_percent_per_skill_level' => 3, 'combat.survivor.maximum_blocked_percent' => 60]);
    startFight($user);

    fightRound($user);

    expect($user->fresh()->health)->toBe(96);
});

test('survivors fight with the best weapon they carry', function () {
    looter(['health' => 50]);
    $user = fighter();
    foreach (['rusty_pipe', 'hunting_rifle'] as $weaponKey) {
        $user->inventoryItems()->create(['item_id' => Item::query()->where('key', $weaponKey)->value('id'), 'quantity' => 1]);
    }
    startFight($user);

    fightRound($user);

    expect($user->fresh('activeWork')->activeWork)
        ->enemy_health->toBe(38)
        ->last_cycle_summary->toContain('you hit for 12 with your Hunting Rifle');
});

test('hurt survivors hit softer', function () {
    looter(['health' => 50]);
    $user = fighter(['health' => 50]);
    startFight($user);

    fightRound($user);

    expect($user->fresh('activeWork')->activeWork->enemy_health)->toBe(46);
});

test('premium survivors fight on the same odds', function () {
    looter(['health' => 50]);
    $user = fighter(['premium_active' => true]);
    startFight($user);

    fightRound($user);

    expect($user->fresh('activeWork')->activeWork->enemy_health)->toBe(45)
        ->and($user->fresh()->health)->toBe(96);
});

// ── Fleeing and dying ────────────────────────────────────────────────────────

test('survivors flee when a blow leaves them at the flee threshold, ending the loop without reward', function () {
    looter(['health' => 50]);
    $user = fighter(['health' => 24]);
    startFight($user);

    fightRound($user);

    expect($user->fresh())
        ->health->toBe(20)
        ->activeWork->toBeNull()
        ->last_work_report->toContain('The Looter hit you for 4 and you fled with 20 health left.')
        ->and($user->fresh()->skillFor('combat_melee')->xp)->toBe(0);
});

test('a killing blow fires the death event with combat as the cause', function () {
    Event::fake([SurvivorDied::class]);
    looter(['health' => 50, 'min_damage' => 30, 'max_damage' => 30]);
    $user = fighter(['health' => 25]);
    startFight($user);

    fightRound($user);

    Event::assertDispatched(SurvivorDied::class, fn (SurvivorDied $event): bool => $event->user->is($user) && $event->cause === HarmCause::Combat);
});

test('dying in a fight stops the work and tells the survivor how they fell', function () {
    looter(['health' => 50, 'min_damage' => 30, 'max_damage' => 30]);
    $user = fighter(['health' => 25]);
    startFight($user);

    fightRound($user);

    expect($user->fresh())
        ->activeWork->toBeNull()
        ->last_work_report->toBeNull()
        ->death_report->toContain('You fell in the fight');
});

// ── Dashboard ────────────────────────────────────────────────────────────────

test('the dashboard names the enemies a combat action fights and the enemy being fought', function () {
    looter();
    $user = fighter();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="action-enemies-clear_the_ruins"', false)
        ->assertSeeText('Fight: Looter')
        ->assertDontSee('data-test="action-enemies-shelter_repair"', false);

    startFight($user);
    fightRound($user);

    $this->actingAs($user->fresh())->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="work-enemy"', false)
        ->assertSeeText('Fighting: Looter')
        ->assertSeeText('5 / 10 health');
});

// ── Admin ────────────────────────────────────────────────────────────────────

test('admins create an enemy with a loot table', function () {
    $admin = User::factory()->admin()->create();
    $kyiv = $admin->location->city;

    Livewire::actingAs($admin)
        ->test(ManageEnemies::class)
        ->call('create')
        ->set('city_id', $kyiv->id)
        ->set('key', 'sewer_rat')
        ->set('name', 'Sewer Rat')
        ->set('description', 'Big as a cat.')
        ->set('skill_key', 'combat_melee')
        ->set('health', 8)
        ->set('min_damage', 1)
        ->set('max_damage', 2)
        ->set('xp_reward', 4)
        ->call('addLoot')
        ->set('loot.0.item_key', 'mutant_hide')
        ->set('loot.0.chance_percent', 40)
        ->set('loot.0.quantity', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(Enemy::query()->where('key', 'sewer_rat')->firstOrFail())
        ->city_id->toBe($kyiv->id)
        ->health->toBe(8)
        ->lootTable()->toBe([['item_key' => 'mutant_hide', 'chance_percent' => 40, 'quantity' => 1]]);
});

test('admins cannot save an enemy with impossible values', function () {
    $admin = User::factory()->admin()->create();

    Livewire::actingAs($admin)
        ->test(ManageEnemies::class)
        ->call('edit', looter()->id)
        ->set('skill_key', 'cooking')
        ->set('min_damage', 6)
        ->set('max_damage', 5)
        ->set('key', 'feral_dog')
        ->call('addLoot')
        ->set('loot.0.item_key', 'not_an_item')
        ->set('loot.0.chance_percent', 0)
        ->call('save')
        ->assertHasErrors([
            'skill_key' => 'in',
            'max_damage' => 'gte',
            'key' => 'unique',
            'loot.0.item_key' => 'exists',
            'loot.0.chance_percent' => 'min',
        ]);
});

test('only admins can manage enemies', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.enemies'))->assertForbidden();
});

test('admins set the attack bonus that makes an item a weapon', function () {
    $admin = User::factory()->admin()->create();
    $item = Item::query()->where('key', 'scrap_metal')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageItems::class)
        ->call('edit', $item->id)
        ->set('attack_bonus', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect($item->fresh())->attack_bonus->toBe(3)->isWeapon()->toBeTrue();
});
