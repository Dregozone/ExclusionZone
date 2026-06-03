<?php

use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Skill;
use App\Models\User;

test('available quests appear on the jobs tab in the pda', function () {
    $user = User::factory()->create();
    Quest::factory()->create(['name' => 'Test Job', 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Test Job');
});

test('user can accept an active quest', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->post(route('quest.accept', $quest))
        ->assertRedirect(route('dashboard'));

    expect($user->userQuests()->where('quest_id', $quest->id)->exists())->toBeTrue();
    expect($user->userQuests()->where('quest_id', $quest->id)->value('status'))->toBe('active');
});

test('user cannot accept an inactive quest', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->post(route('quest.accept', $quest))
        ->assertRedirect();

    expect($user->userQuests()->where('quest_id', $quest->id)->exists())->toBeFalse();
});

test('user cannot accept the same quest twice', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active']);

    $this->actingAs($user)
        ->post(route('quest.accept', $quest))
        ->assertRedirect();

    expect($user->userQuests()->where('quest_id', $quest->id)->count())->toBe(1);
});

test('quest step interact form appears on dashboard when user is in correct city with no item requirement', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => null,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('quest-step.interact', $step));
});

test('quest step interact form does not appear when user is in wrong city', function () {
    $user = User::factory()->create();
    $otherCity = City::query()->where('id', '!=', $user->location->city_id)->first();
    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $otherCity->id,
        'required_item_id' => null,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('quest-step.interact', $step));
});

test('quest step interact form does not appear when required item is missing from inventory', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $item = Item::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => $item->id,
        'required_item_quantity' => 1,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(route('quest-step.interact', $step));
});

test('quest step interact form appears when required item is in inventory', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $item = Item::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => $item->id,
        'required_item_quantity' => 1,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => 2]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('quest-step.interact', $step));
});

test('interacting with a non-item step advances the quest and stores note', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $quest = Quest::factory()->create(['is_active' => true]);

    $step0 = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'interaction_text' => 'I need your help, stranger.',
        'required_item_id' => null,
    ]);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 1,
        'city_id' => $city->id,
        'required_item_id' => null,
    ]);

    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $this->actingAs($user)
        ->post(route('quest-step.interact', $step0))
        ->assertRedirect(route('dashboard'));

    $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
    expect($userQuest->current_step_index)->toBe(1);
    expect($userQuest->status)->toBe('active');
    expect($userQuest->notes)->toContain('I need your help, stranger.');
});

test('completing the last step marks quest as completed and grants xp reward', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $skill = Skill::query()->first();
    $quest = Quest::factory()->create([
        'is_active' => true,
        'reward_skill_id' => $skill->id,
        'reward_xp_amount' => 500,
    ]);

    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => null,
    ]);

    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $this->actingAs($user)
        ->post(route('quest-step.interact', $step))
        ->assertRedirect(route('dashboard'));

    $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
    expect($userQuest->status)->toBe('completed');
    expect($userQuest->completed_at)->not->toBeNull();

    // 500 XP at level 1 (0 XP) levels up twice: level 1→2→3 with 200 XP remaining
    $userSkill = $user->skills()->where('skill_id', $skill->id)->first();
    expect($userSkill->level)->toBe(3);
});

test('completing last step with item requirement consumes the item', function () {
    $user = User::factory()->create();
    $city = $user->location->city;
    $item = Item::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true]);

    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $city->id,
        'required_item_id' => $item->id,
        'required_item_quantity' => 1,
        'consumes_item' => true,
    ]);

    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post(route('quest-step.interact', $step))
        ->assertRedirect(route('dashboard'));

    expect($user->inventoryItems()->where('item_id', $item->id)->exists())->toBeFalse();
});

test('interacting fails when user is in the wrong city', function () {
    $user = User::factory()->create();
    $otherCity = City::query()->where('id', '!=', $user->location->city_id)->first();
    $quest = Quest::factory()->create(['is_active' => true]);

    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $otherCity->id,
        'required_item_id' => null,
    ]);

    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $this->actingAs($user)
        ->post(route('quest-step.interact', $step))
        ->assertRedirect();

    $userQuest = $user->userQuests()->where('quest_id', $quest->id)->first();
    expect($userQuest->current_step_index)->toBe(0);
});
