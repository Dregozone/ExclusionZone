<?php

use App\Actions\Game\BuildCityMenuData;
use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Skill;
use App\Models\User;

// --- Sequential Story Quest Gating ---

test('first story quest with no prerequisite appears as available', function () {
    $user = User::factory()->create();
    Quest::factory()->create([
        'name' => 'Chapter One',
        'quest_type' => 'story',
        'sequence_order' => 1,
        'prerequisite_quest_id' => null,
        'is_active' => true,
    ]);

    $data = (new BuildCityMenuData)($user);

    $available = collect($data['jobs']['available']);
    expect($available->where('type', 'story')->pluck('quest')->map->name)->toContain('Chapter One');
});

test('second story quest does not appear until first is completed', function () {
    $user = User::factory()->create();
    $q1 = Quest::factory()->create([
        'quest_type' => 'story',
        'sequence_order' => 1,
        'prerequisite_quest_id' => null,
        'is_active' => true,
    ]);
    $q2 = Quest::factory()->create([
        'name' => 'Chapter Two',
        'quest_type' => 'story',
        'sequence_order' => 2,
        'prerequisite_quest_id' => $q1->id,
        'is_active' => true,
    ]);

    $data = (new BuildCityMenuData)($user);
    $available = collect($data['jobs']['available']);

    expect($available->where('type', 'story')->pluck('quest')->map->name)->not->toContain('Chapter Two');
});

test('second story quest appears after first is completed', function () {
    $user = User::factory()->create();
    $q1 = Quest::factory()->create([
        'quest_type' => 'story',
        'sequence_order' => 1,
        'prerequisite_quest_id' => null,
        'is_active' => true,
    ]);
    $q2 = Quest::factory()->create([
        'name' => 'Chapter Two',
        'quest_type' => 'story',
        'sequence_order' => 2,
        'prerequisite_quest_id' => $q1->id,
        'is_active' => true,
    ]);

    // Complete q1
    $user->userQuests()->create([
        'quest_id' => $q1->id,
        'status' => 'completed',
        'current_step_index' => 0,
        'completed_at' => now(),
    ]);

    $data = (new BuildCityMenuData)($user);
    $available = collect($data['jobs']['available']);

    expect($available->where('type', 'story')->pluck('quest')->map->name)->toContain('Chapter Two');
});

// --- Repeatable Job: Accept & Re-accept ---

test('repeatable job generates active_requirements on accept', function () {
    $user = User::factory()->create();
    $item1 = Item::factory()->create();
    $item2 = Item::factory()->create();

    $quest = Quest::factory()->create(['is_active' => true, 'is_repeatable' => true, 'quest_type' => 'job']);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'required_item_id' => null,
        'requirement_variants' => null,
        'city_id' => $user->location->city_id,
    ]);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 1,
        'required_item_id' => null,
        'requirement_variants' => [
            ['required_item_id' => $item1->id, 'required_item_quantity' => 1],
            ['required_item_id' => $item2->id, 'required_item_quantity' => 2],
        ],
        'city_id' => $user->location->city_id,
    ]);

    $this->actingAs($user)->post(route('quest.accept', $quest))->assertRedirect();

    $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
    expect($userQuest)->not->toBeNull();
    expect($userQuest->status)->toBe('active');

    $reqs = $userQuest->active_requirements;
    expect($reqs)->toBeArray();
    expect(isset($reqs[1]))->toBeTrue();
    expect($reqs[1]['required_item_id'])->toBeIn([$item1->id, $item2->id]);
});

test('completing a repeatable job sets status to repeatable and increments completion_count', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $skill = Skill::query()->first();

    $quest = Quest::factory()->create([
        'is_active' => true,
        'is_repeatable' => true,
        'quest_type' => 'job',
        'reward_skill_id' => $skill->id,
        'reward_xp_amount' => 100,
    ]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => null,
        'requirement_variants' => null,
    ]);

    $user->userQuests()->create([
        'quest_id' => $quest->id,
        'status' => 'active',
        'current_step_index' => 0,
    ]);

    $this->actingAs($user)->post(route('quest-step.interact', $step))->assertRedirect();

    $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
    expect($userQuest->status)->toBe('repeatable');
    expect($userQuest->completion_count)->toBe(1);
    expect($userQuest->current_step_index)->toBe(0);
});

test('repeatable job can be re-accepted after completion and gets new requirements', function () {
    $user = User::factory()->create();
    $item1 = Item::factory()->create();
    $item2 = Item::factory()->create();

    $quest = Quest::factory()->create(['is_active' => true, 'is_repeatable' => true, 'quest_type' => 'job']);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'required_item_id' => null,
        'requirement_variants' => [
            ['required_item_id' => $item1->id, 'required_item_quantity' => 1],
            ['required_item_id' => $item2->id, 'required_item_quantity' => 1],
        ],
        'city_id' => $user->location->city_id,
    ]);

    // Simulate previously completed run
    $user->userQuests()->create([
        'quest_id' => $quest->id,
        'status' => 'repeatable',
        'current_step_index' => 0,
        'completion_count' => 1,
    ]);

    $this->actingAs($user)->post(route('quest.accept', $quest))->assertRedirect();

    $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
    expect($userQuest->status)->toBe('active');
    expect($userQuest->completion_count)->toBe(1); // count preserved until next completion
    expect($userQuest->notes)->toBeNull();
});

test('repeatable job cannot be re-accepted while still active', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true, 'is_repeatable' => true, 'quest_type' => 'job']);

    $user->userQuests()->create([
        'quest_id' => $quest->id,
        'status' => 'active',
        'current_step_index' => 0,
    ]);

    $this->actingAs($user)->post(route('quest.accept', $quest))->assertRedirect();

    // Still only one row
    expect($user->userQuests()->where('quest_id', $quest->id)->count())->toBe(1);
});

// --- Active Requirements Used for Item Validation ---

test('repeatable job uses active_requirements to validate required item', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $item = Item::factory()->create();

    $quest = Quest::factory()->create(['is_active' => true, 'is_repeatable' => true, 'quest_type' => 'job']);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => null, // default is null
        'requirement_variants' => [
            ['required_item_id' => $item->id, 'required_item_quantity' => 1],
        ],
        'consumes_item' => true,
    ]);

    // active_requirements overrides the step's null required_item_id
    $user->userQuests()->create([
        'quest_id' => $quest->id,
        'status' => 'active',
        'current_step_index' => 0,
        'active_requirements' => [0 => ['required_item_id' => $item->id, 'required_item_quantity' => 1]],
    ]);
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => 2]);

    $this->actingAs($user)->post(route('quest-step.interact', $step))->assertRedirect();

    // item was consumed
    $remaining = $user->inventoryItems()->where('item_id', $item->id)->value('quantity');
    expect($remaining)->toBe(1);
});

// --- Repeatable Jobs Show Run Counter in PDA ---

test('repeatable job available again shows in available section with completion_count', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create([
        'name' => 'Daily Supply Run',
        'is_active' => true,
        'is_repeatable' => true,
        'quest_type' => 'job',
    ]);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'required_item_id' => null,
        'city_id' => $user->location->city_id,
    ]);

    $user->userQuests()->create([
        'quest_id' => $quest->id,
        'status' => 'repeatable',
        'current_step_index' => 0,
        'completion_count' => 3,
    ]);

    $data = (new BuildCityMenuData)($user);
    $available = collect($data['jobs']['available']);

    $entry = $available->firstWhere('type', 'repeatable_job');
    expect($entry)->not->toBeNull();
    expect($entry['completion_count'])->toBe(3);
    expect($entry['quest']->name)->toBe('Daily Supply Run');
});
