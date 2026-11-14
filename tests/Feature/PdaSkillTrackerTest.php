<?php

use App\Models\Skill;
use App\Models\User;

test('each skill row shows its level, xp out of the next level and xp left', function () {
    $user = User::factory()->create();
    $cooking = Skill::query()->where('key', 'cooking')->firstOrFail();
    $user->skills()->where('skill_id', $cooking->id)->update(['level' => 2, 'xp' => 40]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSeeInOrder([
            'data-test="skill-row-cooking"',
            'images/work/cooking.jpg',
            'Cooking',
            'LV <span class="text-base leading-none font-semibold text-emerald-400">2</span>',
            'aria-valuemax="200"',
            'aria-valuenow="40"',
            'width: 20%',
            '40</span> / 200 XP',
            '160 to LV3',
        ], false);
});

test('the skill tracker totals the levels of every skill', function () {
    $user = User::factory()->create();
    $user->skills()->update(['level' => 3]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertSeeInOrder(['data-test="skills-total-level"', '39'], false);
});

test('a skill without its own picture falls back to the default scene', function () {
    $skill = Skill::query()->create(['key' => 'whittling', 'display_name' => 'Whittling', 'description' => 'Carve wood']);

    expect($skill->imagePath())->toBe('images/work/default.jpg');
});

test('gaining experience carries leftover xp into the next level', function () {
    $user = User::factory()->create();
    $userSkill = $user->skills()->firstOrFail();
    $userSkill->update(['level' => 2, 'xp' => 150]);

    $levelsGained = $userSkill->gainExperience(80);

    expect($levelsGained)->toBe(1)
        ->and($userSkill->fresh()->only(['level', 'xp']))->toBe(['level' => 3, 'xp' => 30])
        ->and($userSkill->xpToNextLevel())->toBe(270)
        ->and($userSkill->progressPercent())->toBe(10);
});
