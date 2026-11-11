<?php

use App\Actions\Game\DealWithTrouble;
use App\Actions\Game\MeetTroubleOnArrival;
use App\Events\SurvivorDied;
use App\HarmCause;
use App\Livewire\Admin\ManageTroubleTypes;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\TroubleEncounter;
use App\Models\TroubleType;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Lottery;
use Livewire\Livewire;

beforeEach(function () {
    config(['trouble.enabled' => true]);
});

/**
 * Leave only the barricade looters able to cause trouble, with a fixed amount of damage.
 */
function onlyBarricadeLooters(int $damage = 10): TroubleType
{
    $looters = TroubleType::query()->where('key', 'barricade_looters')->firstOrFail();
    $looters->update(['damage_min' => $damage, 'damage_max' => $damage]);
    TroubleType::query()->whereKeyNot($looters->id)->delete();

    return $looters->fresh();
}

/**
 * A survivor in clear-weather Kyiv, already held at a neighboring city by trouble.
 *
 * @return array{0: User, 1: City, 2: City}
 */
function survivorHeldByTrouble(TroubleType $troubleType, array $attributes = []): array
{
    $user = User::factory()->create($attributes);
    $origin = $user->location->city;
    $origin->update(['weather' => Weather::Clear]);
    $troubled = $origin->neighbors()->firstOrFail();
    $troubled->update(['weather' => Weather::Clear]);
    $user->location()->update(['city_id' => $troubled->id, 'country_id' => $troubled->country_id]);

    TroubleEncounter::query()->create([
        'user_id' => $user->id,
        'city_id' => $troubled->id,
        'trouble_type_id' => $troubleType->id,
        'origin_city_id' => $origin->id,
    ]);

    return [$user->fresh(), $origin, $troubled];
}

function packTroubleGoods(User $user, string $itemKey, int $quantity): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);

    return $item;
}

// ── Meeting trouble on arrival ───────────────────────────────────────────────

test('trouble on a first arrival holds the survivor at the city and blocks everything there', function () {
    $looters = onlyBarricadeLooters();
    $user = User::factory()->create();
    $destination = $user->location->city->neighbors()->firstOrFail();
    Lottery::alwaysWin();
    $this->freezeTime();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);
    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee("You arrived in {$destination->city}, but Barricade looters block the way in.")
        ->assertSee('data-test="trouble-panel"', false);

    expect($user->fresh()->location->city_id)->toBe($destination->id);
    $this->assertDatabaseHas('trouble_encounters', ['user_id' => $user->id, 'city_id' => $destination->id, 'trouble_type_id' => $looters->id]);
    $this->assertDatabaseHas('city_visits', ['user_id' => $user->id, 'city_id' => $destination->id]);

    $action = CityAction::query()->where('city_id', $destination->id)->firstOrFail();
    $this->post(route('city-action.store'), ['city_action_id' => $action->id])
        ->assertSessionHas('toast.text', 'Trouble is blocking your way. Deal with it before doing anything else here.');
    $this->post(route('travel.store'), ['city_id' => $user->fresh()->location->city->neighbors()->firstOrFail()->id])
        ->assertSessionHas('toast.text', 'Trouble is blocking your way. Deal with it before doing anything else here.');
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('a survivor starts out having visited their starting city', function () {
    $user = User::factory()->create();

    $this->assertDatabaseHas('city_visits', ['user_id' => $user->id, 'city_id' => $user->location->city_id]);
});

test('returning to a city only rolls the return-visit share of its trouble chance', function () {
    onlyBarricadeLooters();
    config(['trouble.return_visit_chance_percent' => 0]);
    $user = User::factory()->create();
    $destination = $user->location->city->neighbors()->firstOrFail();
    $user->cityVisits()->create(['city_id' => $destination->id]);
    Lottery::alwaysWin();
    $this->freezeTime();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);
    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();

    $this->get(route('dashboard'))->assertSee("You arrived in {$destination->city} and refreshed your options.");
    $this->assertDatabaseMissing('trouble_encounters', ['user_id' => $user->id]);
});

test('the return-visit chance is a share of the city trouble chance', function () {
    config(['trouble.return_visit_chance_percent' => 25]);
    $city = new City(['trouble_chance_pct' => 80]);
    $meetTrouble = app(MeetTroubleOnArrival::class);

    expect($meetTrouble->chancePercent($city, isFirstVisit: true))->toBe(80)
        ->and($meetTrouble->chancePercent($city, isFirstVisit: false))->toBe(20);
});

test('no trouble is met when it is switched off', function () {
    onlyBarricadeLooters();
    config(['trouble.enabled' => false]);
    $user = User::factory()->create();
    $destination = $user->location->city->neighbors()->firstOrFail();
    Lottery::alwaysWin();
    $this->freezeTime();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);
    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();
    $this->get(route('dashboard'))->assertOk();

    $this->assertDatabaseMissing('trouble_encounters', ['user_id' => $user->id]);
    $this->assertDatabaseHas('city_visits', ['user_id' => $user->id, 'city_id' => $destination->id]);
});

test('a multi-stop route stops at a troubled city and carries on once the way is bought', function () {
    onlyBarricadeLooters();
    $user = User::factory()->create();
    $origin = $user->location->city;
    $directCityIds = $origin->neighbors()->pluck('cities.id')->push($origin->id);
    $stopover = $origin->neighbors()->get()
        ->first(fn (City $neighbor): bool => $neighbor->neighbors()->whereNotIn('cities.id', $directCityIds)->exists());
    $destination = $stopover->neighbors()->whereNotIn('cities.id', $directCityIds)->firstOrFail();
    $scrap = packTroubleGoods($user, 'scrap_metal', 10);
    Lottery::alwaysWin();
    $this->freezeTime();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id, 'via' => [$stopover->id]]);
    $this->travel($user->fresh('activeWork')->activeWork->duration_seconds)->seconds();
    $this->get(route('dashboard'))->assertSee("Get past them and your route carries on to {$destination->city}.");

    expect($user->fresh()->location->city_id)->toBe($stopover->id);
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);

    $this->post(route('trouble.buy-in'), ['items' => [$scrap->id => 4]])
        ->assertSessionHas('status', "You handed over 4x Scrap Metal, and the Barricade looters let you through. You set off for {$destination->city} along the rest of your route.");

    $this->assertDatabaseHas('user_works', ['user_id' => $user->id, 'work_type' => 'travel', 'origin_city_id' => $stopover->id, 'destination_city_id' => $destination->id]);
});

// ── Attacking ────────────────────────────────────────────────────────────────

test('winning a fight clears the trouble, pays combat XP and loot, and costs part of the damage', function () {
    $looters = onlyBarricadeLooters(damage: 10);
    [$user] = survivorHeldByTrouble($looters);
    Lottery::alwaysWin();

    $this->actingAs($user)->post(route('trouble.attack'))
        ->assertSessionHas('status', 'You drove off the Barricade looters (+45 Melee Combat XP, +2 Metal Parts, -5 health).');

    $user = $user->fresh('skills.skill');
    expect($user->health)->toBe(95)
        ->and($user->skillFor('combat_melee')->xp)->toBe(45)
        ->and($user->inventoryItems()->whereRelation('item', 'key', 'metal_parts')->value('quantity'))->toBe(2);
    $this->assertDatabaseMissing('trouble_encounters', ['user_id' => $user->id]);
});

test('losing a fight costs the full damage and leaves the trouble in place', function () {
    $looters = onlyBarricadeLooters(damage: 10);
    [$user] = survivorHeldByTrouble($looters);
    Lottery::alwaysLose();

    $this->actingAs($user)->post(route('trouble.attack'))
        ->assertSessionHas('status', 'You attacked the Barricade looters and were beaten back (-10 health). They still block the way.');

    expect($user->fresh()->health)->toBe(90)
        ->and($user->fresh('skills.skill')->skillFor('combat_melee')->xp)->toBe(0);
    $this->assertDatabaseHas('trouble_encounters', ['user_id' => $user->id]);
});

test('a fight that takes the last of the survivor health is a death by trouble', function () {
    $looters = onlyBarricadeLooters(damage: 30);
    [$user] = survivorHeldByTrouble($looters, ['health' => 20]);
    Lottery::alwaysLose();
    Event::fake([SurvivorDied::class]);

    $this->actingAs($user)->post(route('trouble.attack'))
        ->assertSessionHas('status', 'You attacked the Barricade looters and lost.');

    Event::assertDispatched(SurvivorDied::class, fn (SurvivorDied $event): bool => $event->user->is($user) && $event->cause === HarmCause::Trouble);
});

test('the chance to win depends on combat skill against the fighters and on health', function (int $skillLevel, int $health, int $expectedChance) {
    $looters = onlyBarricadeLooters();
    [$user] = survivorHeldByTrouble($looters, ['health' => $health]);
    $user->skillFor('combat_melee')->update(['level' => $skillLevel]);

    expect(app(DealWithTrouble::class)->winChancePercent($user->fresh('skills.skill'), $looters))->toBe($expectedChance);
})->with([
    'novice at full health' => [1, 100, 50],
    'novice at half health' => [1, 50, 40],
    'matched level at full health' => [4, 100, 65],
    'far stronger survivor' => [30, 100, 95],
    'badly hurt novice' => [1, 5, 31],
    'hopeless fight' => [1, 0, 30],
]);

// ── Buying the way in ────────────────────────────────────────────────────────

test('handing over goods worth the demand clears the trouble and takes the goods', function () {
    $looters = onlyBarricadeLooters();
    [$user] = survivorHeldByTrouble($looters);
    $scrap = packTroubleGoods($user, 'scrap_metal', 6);

    $this->actingAs($user)->post(route('trouble.buy-in'), ['items' => [$scrap->id => 4]])
        ->assertSessionHas('status', 'You handed over 4x Scrap Metal, and the Barricade looters let you through.');

    expect($user->inventoryItems()->where('item_id', $scrap->id)->value('quantity'))->toBe(2);
    $this->assertDatabaseMissing('trouble_encounters', ['user_id' => $user->id]);
});

test('an offer worth less than the demand is refused and nothing is taken', function () {
    $looters = onlyBarricadeLooters();
    [$user] = survivorHeldByTrouble($looters);
    $scrap = packTroubleGoods($user, 'scrap_metal', 6);

    $this->actingAs($user)->post(route('trouble.buy-in'), ['items' => [$scrap->id => 3]])
        ->assertSessionHas('toast.text', 'The Barricade looters want goods worth 4. Your offer is only worth 3.');

    expect($user->inventoryItems()->where('item_id', $scrap->id)->value('quantity'))->toBe(6);
    $this->assertDatabaseHas('trouble_encounters', ['user_id' => $user->id]);
});

test('quest items and goods the survivor does not carry cannot be handed over', function (bool $isQuestItem, int $offered) {
    $looters = onlyBarricadeLooters();
    [$user] = survivorHeldByTrouble($looters);
    $scrap = packTroubleGoods($user, 'scrap_metal', 5);
    $scrap->update(['is_quest_item' => $isQuestItem]);

    $this->actingAs($user)->post(route('trouble.buy-in'), ['items' => [$scrap->id => $offered]])
        ->assertSessionHas('toast.text', 'You do not carry everything you offered.');

    expect($user->inventoryItems()->where('item_id', $scrap->id)->value('quantity'))->toBe(5);
})->with([
    'quest item' => [true, 4],
    'more than carried' => [false, 6],
]);

test('buying the way in is only offered when the survivor carries enough', function () {
    $looters = onlyBarricadeLooters();
    [$user] = survivorHeldByTrouble($looters);
    packTroubleGoods($user, 'scrap_metal', 3);

    $this->actingAs($user)->get(route('dashboard'))
        ->assertSee('data-test="trouble-cannot-buy-in"', false)
        ->assertDontSee('data-test="trouble-buy-in"', false);
});

// ── Running away ─────────────────────────────────────────────────────────────

test('running away sets off back to the previous city on a trip that cannot be canceled', function () {
    $looters = onlyBarricadeLooters();
    [$user, $origin, $troubled] = survivorHeldByTrouble($looters);
    Lottery::alwaysWin();
    $this->freezeTime();

    $this->actingAs($user)->post(route('trouble.run'))
        ->assertSessionHas('status', "You turned and ran for {$origin->city}. There is no turning back until you get there.");

    $retreat = $user->fresh('activeWork')->activeWork;
    expect($retreat->is_retreat)->toBeTrue()
        ->and($retreat->origin_city_id)->toBe($troubled->id)
        ->and($retreat->destination_city_id)->toBe($origin->id);
    $this->assertDatabaseMissing('trouble_encounters', ['user_id' => $user->id]);

    $this->get(route('dashboard'))->assertDontSee('data-test="cancel-work"', false);
    $this->post(route('work.cancel'))
        ->assertSessionHas('toast.text', "There is no turning back now. Keep running until you reach {$origin->city}.");

    $this->travel($retreat->duration_seconds)->seconds();
    $this->get(route('dashboard'))->assertSee("You made it back to {$origin->city} in one piece.");

    expect($user->fresh()->location->city_id)->toBe($origin->id);
    $this->assertDatabaseMissing('trouble_encounters', ['user_id' => $user->id]);
});

test('a survivor with no trouble cannot attack, pay or run', function (string $routeName) {
    $this->actingAs(User::factory()->create())->post(route($routeName), ['items' => [1 => 1]])
        ->assertSessionHas('toast.text', 'There is no trouble to deal with.');
})->with(['trouble.attack', 'trouble.buy-in', 'trouble.run']);

// ── Admin configuration ──────────────────────────────────────────────────────

test('admins add a new kind of trouble', function () {
    $admin = User::factory()->admin()->create();
    $hide = Item::query()->where('key', 'mutant_hide')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageTroubleTypes::class)
        ->call('create')
        ->set('key', 'raider_patrol')
        ->set('name', 'Raider patrol')
        ->set('description', 'Raiders sweep the road in.')
        ->set('skill_key', 'combat_ranged')
        ->set('fighter_level', 7)
        ->set('damage_min', 5)
        ->set('damage_max', 20)
        ->set('xp_reward', 70)
        ->set('loot_item_id', $hide->id)
        ->set('loot_quantity', 2)
        ->set('demand_value', 8)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('trouble_types', [
        'key' => 'raider_patrol',
        'skill_key' => 'combat_ranged',
        'fighter_level' => 7,
        'loot_item_id' => $hide->id,
        'demand_value' => 8,
    ]);
});

test('trouble must test a combat skill and deal no less than its minimum damage', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(ManageTroubleTypes::class)
        ->call('edit', TroubleType::query()->firstOrFail()->id)
        ->set('skill_key', 'cooking')
        ->set('damage_min', 10)
        ->set('damage_max', 5)
        ->call('save')
        ->assertHasErrors(['skill_key' => 'in', 'damage_max' => 'gte']);
});

test('only admins can manage trouble', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.trouble-types'))
        ->assertForbidden();
});
