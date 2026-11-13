<?php

use App\Actions\Game\BuildManualCatalogue;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Enemy;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;

/**
 * Give a skill a known set of actions in Kyiv, so the manual's ordering and item flow can be checked
 * against data the test owns.
 *
 * @return array{skill: Skill, city: City, input: Item, output: Item}
 */
function manualFixture(): array
{
    $skill = Skill::query()->create(['key' => 'manual_testing', 'display_name' => 'Manual Testing', 'description' => 'Proving the manual works']);
    $city = City::query()->where('city', 'Kyiv')->firstOrFail();
    $input = Item::factory()->create(['key' => 'test_ore', 'name' => 'Test Ore']);
    $output = Item::factory()->create(['key' => 'test_ingot', 'name' => 'Test Ingot']);

    foreach ([[12, 'Advanced smelting', [['item_key' => 'test_ore', 'quantity' => 3]]], [1, 'Basic smelting', null], [5, 'Steady smelting', null]] as [$level, $label, $requiredItems]) {
        CityAction::query()->create([
            'city_id' => $city->id,
            'action_key' => str($label)->snake()->toString(),
            'label' => $label,
            'description' => "{$label} for the test.",
            'skill_key' => $skill->key,
            'min_level' => $level,
            'risk_level' => 'low',
            'base_duration_seconds' => 20,
            'hunger_cost' => 1,
            'fatigue_cost' => 1,
            'reward_profile' => ['xp' => 25, 'item_key' => 'test_ingot', 'quantity' => 2],
            'required_items' => $requiredItems,
        ]);
    }

    return compact('skill', 'city', 'input', 'output');
}

test('every manual page is public', function (string $url) {
    $this->get($url)->assertOk();
})->with([
    'overview' => fn () => route('manual.index'),
    'getting started' => fn () => route('manual.guide', 'getting-started'),
    'city menu' => fn () => route('manual.guide', 'city-menu'),
    'skills and work' => fn () => route('manual.guide', 'skills-and-work'),
    'survival' => fn () => route('manual.guide', 'survival'),
    'travel' => fn () => route('manual.guide', 'travel'),
    'combat' => fn () => route('manual.guide', 'combat'),
    'trading and radio' => fn () => route('manual.guide', 'trading-and-radio'),
    'brain scorcher' => fn () => route('manual.guide', 'brain-scorcher'),
    'skills' => fn () => route('manual.skills'),
    'a skill' => fn () => route('manual.skill', 'scavenging'),
    'locations' => fn () => route('manual.locations'),
    'a location' => fn () => route('manual.location', 'pripyat'),
    'items' => fn () => route('manual.items'),
    'an item' => fn () => route('manual.item', 'scrap_metal'),
    'quests' => fn () => route('manual.quests'),
    'faq' => fn () => route('manual.faq'),
]);

test('the manual is linked from the sidebar and the landing page', function () {
    $this->get(route('high-scores'))->assertSee(route('manual.index'), false);
    $this->get(route('home'))->assertSee(route('manual.guide', 'getting-started'), false);
});

test('unknown manual pages are not found', function (string $url) {
    $this->get($url)->assertNotFound();
})->with([
    'guide' => '/manual/not-a-guide',
    'skill' => '/manual/skills/not-a-skill',
    'location' => '/manual/locations/atlantis',
    'item' => '/manual/items/not-an-item',
]);

test('a skill page lists its actions from the lowest level up with where to do them and what they need and give', function () {
    ['skill' => $skill] = manualFixture();

    $this->get(route('manual.skill', $skill->key))
        ->assertOk()
        ->assertSeeInOrder(['Level 1', 'Basic smelting', 'Level 5', 'Steady smelting', 'Level 12', 'Advanced smelting'])
        ->assertSee('Kyiv')
        ->assertSee(route('manual.item', 'test_ore'), false)
        ->assertSee(route('manual.item', 'test_ingot'), false);
});

test('a skill page starts the level ladder at the signed-in survivor’s own level', function () {
    ['skill' => $skill] = manualFixture();
    $user = User::factory()->create();
    UserSkill::query()->where('user_id', $user->id)->where('skill_id', $skill->id)->update(['level' => 7, 'xp' => 40]);

    $this->actingAs($user)
        ->get(route('manual.skill', $skill->key))
        ->assertOk()
        ->assertSee('level: 7,', false)
        ->assertSee('Your level')
        ->assertSee('40 / 700 XP');
});

test('a guest sets their own level on the skill ladder', function () {
    ['skill' => $skill] = manualFixture();

    $this->get(route('manual.skill', $skill->key))
        ->assertOk()
        ->assertSee('level: 1,', false)
        ->assertSee('My level')
        ->assertSee('to fill this in for you');
});

test('a removed action disappears from the manual', function () {
    ['skill' => $skill] = manualFixture();
    CityAction::query()->where('action_key', 'steady_smelting')->delete();

    $this->get(route('manual.skill', $skill->key))
        ->assertOk()
        ->assertSee('Basic smelting')
        ->assertDontSee('Steady smelting');
});

test('an item page shows which actions make it, which use it up and which enemies drop it', function () {
    ['city' => $city, 'input' => $input, 'output' => $output] = manualFixture();
    Enemy::factory()->create([
        'city_id' => $city->id,
        'name' => 'Ore Golem',
        'loot' => [['item_key' => 'test_ore', 'chance_percent' => 35, 'quantity' => 2]],
    ]);

    $this->get(route('manual.item', $output->key))
        ->assertOk()
        ->assertSee('Made or gathered by')
        ->assertSee('Basic smelting');

    $this->get(route('manual.item', $input->key))
        ->assertOk()
        ->assertSee('Used up by')
        ->assertSee('Advanced smelting')
        ->assertDontSee('Made or gathered by')
        ->assertSee('Ore Golem')
        ->assertSee('35%');
});

test('a location page lists its actions and its routes out', function () {
    manualFixture();

    $this->get(route('manual.location', 'kyiv'))
        ->assertOk()
        ->assertSee('Every survivor starts here')
        ->assertSee('Basic smelting')
        ->assertSee(route('manual.location', 'warsaw'), false);
});

test('location pages are found by a slug of the city name', function () {
    $this->get(route('manual.location', 'st-petersburg'))
        ->assertOk()
        ->assertSee('St Petersburg');
});

test('the search index covers guides, skills, locations, items and actions', function () {
    manualFixture();

    $entries = collect($this->getJson(route('manual.search'))->assertOk()->json());

    expect($entries->pluck('type')->unique()->sort()->values()->all())->toBe(['Action', 'Guide', 'Item', 'Location', 'Skill'])
        ->and($entries->firstWhere('title', 'Basic smelting')['url'])->toBe(route('manual.location', 'kyiv').'#action-basic_smelting')
        ->and($entries->firstWhere('title', 'Test Ingot')['url'])->toBe(route('manual.item', 'test_ingot'));
});

test('the manual uses the same levelling curve as the game', function () {
    $skillProgress = UserSkill::query()->whereBelongsTo(User::factory()->create())->firstOrFail();
    $skillProgress->gainExperience(BuildManualCatalogue::totalExperienceForLevel(10));

    expect($skillProgress->level)->toBe(10)
        ->and($skillProgress->xp)->toBe(0)
        ->and(BuildManualCatalogue::experienceToNextLevel(10))->toBe(1000);
});

test('the manual shortens timers by skill level but never below the minimum', function (int $level, int $seconds) {
    expect(BuildManualCatalogue::cycleSecondsAtLevel(40, $level))->toBe($seconds);
})->with([
    'level 1' => [1, 40],
    'level 11' => [11, 36],
    'level 90' => [90, 10],
]);
