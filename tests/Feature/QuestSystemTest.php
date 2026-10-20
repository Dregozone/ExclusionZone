<?php

use App\Actions\Game\BuildCityMenuData;
use App\Livewire\Admin\ManageQuests;
use App\Models\City;
use App\Models\CityAction;
use App\Models\Item;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

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

// --- Multi-Item Requirements ---

test('step with several required item types fails and consumes nothing when one is missing', function () {
    $user = User::factory()->create();
    $ammo = Item::factory()->create();
    $rations = Item::factory()->create();

    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_item_id' => $ammo->id,
        'required_item_quantity' => 3,
        'required_items' => [['required_item_id' => $rations->id, 'required_item_quantity' => 2]],
        'consumes_item' => true,
    ]);

    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);
    $user->inventoryItems()->create(['item_id' => $ammo->id, 'quantity' => 3]);
    $user->inventoryItems()->create(['item_id' => $rations->id, 'quantity' => 1]);

    $this->actingAs($user)
        ->post(route('quest-step.interact', $step))
        ->assertSessionHas('toast.text', 'You need 2x '.$rations->name.' to proceed.');

    expect($user->userQuests()->where('quest_id', $quest->id)->value('status'))->toBe('active');
    expect($user->inventoryItems()->where('item_id', $ammo->id)->value('quantity'))->toBe(3);
    expect($user->inventoryItems()->where('item_id', $rations->id)->value('quantity'))->toBe(1);
});

test('step with several required item types consumes every item when all are held', function () {
    $user = User::factory()->create();
    $ammo = Item::factory()->create();
    $rations = Item::factory()->create();

    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_item_id' => $ammo->id,
        'required_item_quantity' => 3,
        'required_items' => [['required_item_id' => $rations->id, 'required_item_quantity' => 2]],
        'consumes_item' => true,
    ]);

    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);
    $user->inventoryItems()->create(['item_id' => $ammo->id, 'quantity' => 4]);
    $user->inventoryItems()->create(['item_id' => $rations->id, 'quantity' => 2]);

    $this->actingAs($user)->post(route('quest-step.interact', $step))->assertRedirect(route('dashboard'));

    expect($user->userQuests()->where('quest_id', $quest->id)->value('status'))->toBe('completed');
    expect($user->inventoryItems()->where('item_id', $ammo->id)->value('quantity'))->toBe(1);
    expect($user->inventoryItems()->where('item_id', $rations->id)->exists())->toBeFalse();
});

test('repeatable job variant listing several items requires and consumes all of them', function () {
    $user = User::factory()->create();
    $water = Item::factory()->create();
    $scrap = Item::factory()->create();
    $bundle = [
        ['required_item_id' => $water->id, 'required_item_quantity' => 2],
        ['required_item_id' => $scrap->id, 'required_item_quantity' => 1],
    ];

    $quest = Quest::factory()->create(['is_active' => true, 'is_repeatable' => true, 'quest_type' => 'job']);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'requirement_variants' => [$bundle],
        'consumes_item' => true,
    ]);

    $this->actingAs($user)->post(route('quest.accept', $quest));
    expect($user->userQuests()->where('quest_id', $quest->id)->first()->active_requirements[0])->toBe($bundle);

    $user->inventoryItems()->create(['item_id' => $water->id, 'quantity' => 2]);
    $this->post(route('quest-step.interact', $step))->assertSessionHas('toast.variant', 'danger');
    expect($user->userQuests()->where('quest_id', $quest->id)->value('status'))->toBe('active');

    $user->inventoryItems()->create(['item_id' => $scrap->id, 'quantity' => 1]);
    $this->actingAs($user->fresh())->post(route('quest-step.interact', $step))->assertRedirect(route('dashboard'));

    expect($user->userQuests()->where('quest_id', $quest->id)->value('status'))->toBe('repeatable');
    expect($user->inventoryItems()->whereIn('item_id', [$water->id, $scrap->id])->exists())->toBeFalse();
});

// --- City Action Count Requirements ---

function completeCityAction(User $user, CityAction $action): TestResponse
{
    test()->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    Carbon::setTestNow(now()->addSeconds($user->fresh('activeWork')->activeWork->duration_seconds + 1));
    $response = test()->post(route('work.complete'));
    Carbon::setTestNow();

    return $response;
}

test('completing the required city action counts towards the current quest step', function () {
    $user = User::factory()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $otherAction = CityAction::query()->where('action_key', 'shelter_repair')->firstOrFail();

    $quest = Quest::factory()->create(['name' => 'Clear the Lanes', 'is_active' => true]);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_city_action_id' => $patrols->id,
        'required_action_count' => 3,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    completeCityAction($user, $patrols)
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'Job progress: Clear the Lanes: District patrols 1/3.'));
    completeCityAction($user, $otherAction);

    expect($user->userQuests()->where('quest_id', $quest->id)->value('step_action_progress'))->toBe(1);
});

test('city action progress stops at the required count', function () {
    $user = User::factory()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $quest = Quest::factory()->create(['is_active' => true]);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_city_action_id' => $patrols->id,
        'required_action_count' => 2,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0, 'step_action_progress' => 2]);

    completeCityAction($user, $patrols);

    expect($user->userQuests()->where('quest_id', $quest->id)->value('step_action_progress'))->toBe(2);
});

test('city action step cannot be handed in until the required count is reached', function () {
    $user = User::factory()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_city_action_id' => $patrols->id,
        'required_action_count' => 3,
    ]);
    QuestStep::factory()->create(['quest_id' => $quest->id, 'step_order' => 1, 'city_id' => $user->location->city_id]);
    $userQuest = $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0, 'step_action_progress' => 2]);

    $this->actingAs($user)
        ->post(route('quest-step.interact', $step))
        ->assertSessionHas('toast.text', 'You need to complete District patrols 1 more time to proceed.');
    expect($userQuest->fresh()->current_step_index)->toBe(0);

    $userQuest->update(['step_action_progress' => 3]);
    $this->post(route('quest-step.interact', $step))->assertRedirect(route('dashboard'));

    expect($userQuest->fresh()->current_step_index)->toBe(1);
    expect($userQuest->fresh()->step_action_progress)->toBe(0);
});

test('dashboard shows city action progress and every required item for the current step', function () {
    $user = User::factory()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $ammo = Item::factory()->create(['name' => 'Ammo Crate']);
    $rations = Item::factory()->create(['name' => 'Sealed Rations']);

    $quest = Quest::factory()->create(['is_active' => true]);
    QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_item_id' => $ammo->id,
        'required_item_quantity' => 3,
        'required_items' => [['required_item_id' => $rations->id, 'required_item_quantity' => 2]],
        'required_city_action_id' => $patrols->id,
        'required_action_count' => 3,
    ]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0, 'step_action_progress' => 1]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('District patrols 1/3')
        ->assertSee('Ammo Crate ×3, Sealed Rations ×2');
});

test('field contact stays hidden until the city action count is reached', function () {
    $user = User::factory()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    $quest = Quest::factory()->create(['is_active' => true]);
    $step = QuestStep::factory()->create([
        'quest_id' => $quest->id,
        'step_order' => 0,
        'city_id' => $user->location->city_id,
        'required_city_action_id' => $patrols->id,
        'required_action_count' => 2,
    ]);
    $userQuest = $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0, 'step_action_progress' => 1]);

    $this->actingAs($user)->get(route('dashboard'))->assertDontSee(route('quest-step.interact', $step));

    $userQuest->update(['step_action_progress' => 2]);

    $this->get(route('dashboard'))->assertSee(route('quest-step.interact', $step));
});

// --- Admin Quest Editor ---

test('admin can save a step with extra required items and a city action count', function () {
    $admin = User::factory()->admin()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $item = Item::factory()->create();

    Livewire::actingAs($admin)
        ->test(ManageQuests::class)
        ->call('create')
        ->set('name', 'Patrol Duty')
        ->set('description', 'Walk the lanes.')
        ->call('addStep')
        ->set('steps.0.city_id', $patrols->city_id)
        ->set('steps.0.person_of_interest', 'Sergeant')
        ->set('steps.0.action_label', 'Report in')
        ->set('steps.0.interaction_text', 'Good work.')
        ->set('steps.0.required_items_json', json_encode([['required_item_id' => $item->id, 'required_item_quantity' => 2]]))
        ->set('steps.0.required_city_action_id', $patrols->id)
        ->set('steps.0.required_action_count', 4)
        ->call('save')
        ->assertHasNoErrors();

    $step = Quest::query()->where('name', 'Patrol Duty')->firstOrFail()->steps->first();
    expect($step->required_items)->toBe([['required_item_id' => $item->id, 'required_item_quantity' => 2]]);
    expect($step->required_city_action_id)->toBe($patrols->id);
    expect($step->required_action_count)->toBe(4);
});

test('admin must give a count when a step requires a city action', function () {
    $admin = User::factory()->admin()->create();
    $patrols = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();

    Livewire::actingAs($admin)
        ->test(ManageQuests::class)
        ->call('create')
        ->set('name', 'Patrol Duty')
        ->set('description', 'Walk the lanes.')
        ->call('addStep')
        ->set('steps.0.city_id', $patrols->city_id)
        ->set('steps.0.person_of_interest', 'Sergeant')
        ->set('steps.0.action_label', 'Report in')
        ->set('steps.0.interaction_text', 'Good work.')
        ->set('steps.0.required_city_action_id', $patrols->id)
        ->call('save')
        ->assertHasErrors(['steps.0.required_action_count']);
});
