<?php

use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;

test('high scores page is publicly accessible', function () {
    $this->get(route('high-scores'))
        ->assertOk()
        ->assertSee('High Scores');
});

test('high scores page lists all skills', function () {
    $this->get(route('high-scores'))
        ->assertOk()
        ->assertSee(Skill::query()->value('display_name'));
});

test('high scores page shows the top player for a skill by level then xp', function () {
    $leader = User::factory()->create(['name' => 'TopScout']);
    $other = User::factory()->create(['name' => 'LowScout']);
    $skill = Skill::query()->first();

    UserSkill::query()
        ->where('user_id', $leader->id)
        ->where('skill_id', $skill->id)
        ->update(['level' => 99, 'xp' => 99999]);

    UserSkill::query()
        ->where('user_id', $other->id)
        ->where('skill_id', $skill->id)
        ->update(['level' => 1, 'xp' => 10]);

    $this->get(route('high-scores'))
        ->assertOk()
        ->assertSee('TopScout');
});

test('high scores page shows a dash for skills with no players', function () {
    DB::table('skills')->insert([
        'key' => 'orphan_skill',
        'display_name' => 'Orphan Skill',
        'description' => 'A skill no one has.',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('high-scores'))
        ->assertOk()
        ->assertSee('Orphan Skill')
        ->assertSee('—');
});
