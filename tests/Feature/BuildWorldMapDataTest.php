<?php

use App\Actions\Game\BuildWorldMapData;
use App\Models\City;
use App\Models\Quest;
use App\Models\QuestStep;
use App\Models\User;

test('guests get cities and routes but no player position', function () {
    $mapData = (new BuildWorldMapData)(null);

    $kyiv = collect($mapData['nodes'])->firstWhere('label', 'Kyiv');
    $kyivToWarsaw = collect($mapData['edges'])->first(
        fn (array $edge): bool => $edge['from'] === $kyiv['id'] && $edge['to'] === City::query()->where('city', 'Warsaw')->value('id'),
    );

    expect($mapData['player'])->toBeNull()
        ->and($kyiv)->toMatchArray(['country' => 'Ukraine', 'continent' => 'Europe', 'weather' => 'Clear'])
        ->and($kyivToWarsaw['duration_seconds'])->toBeInt();
});

test('an idle player can travel from their current city', function () {
    $user = User::factory()->create();

    $player = (new BuildWorldMapData)($user)['player'];

    expect($player)->toMatchArray([
        'current_city_id' => $user->location->city_id,
        'can_travel' => true,
        'travel_restriction' => null,
        'transit' => null,
        'objectives' => [],
    ]);
});

test('a travelling player is shown in transit and cannot start another route', function () {
    $user = User::factory()->create();
    $originId = $user->location->city_id;
    $warsaw = City::query()->where('city', 'Warsaw')->firstOrFail();
    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $warsaw->id]);

    $player = (new BuildWorldMapData)($user->fresh())['player'];

    expect($player)->toMatchArray([
        'current_city_id' => $originId,
        'can_travel' => false,
        'travel_restriction' => 'Finish or cancel your current work before starting another action or route.',
        'transit' => ['from' => $originId, 'to' => $warsaw->id],
    ]);
});

test('the city of the current step of each active job is marked as an objective', function () {
    $user = User::factory()->create();
    $moscow = City::query()->where('city', 'Moscow')->firstOrFail();
    $quest = Quest::factory()->create(['name' => 'Cold Trail', 'is_active' => true]);
    QuestStep::factory()->create(['quest_id' => $quest->id, 'step_order' => 0, 'city_id' => $moscow->id, 'action_label' => 'Meet the courier', 'required_item_id' => null]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'active', 'current_step_index' => 0]);

    $objectives = (new BuildWorldMapData)($user)['player']['objectives'];

    expect($objectives)->toBe([
        ['city_id' => $moscow->id, 'quest' => 'Cold Trail', 'action' => 'Meet the courier'],
    ]);
});

test('completed jobs are not marked as objectives', function () {
    $user = User::factory()->create();
    $quest = Quest::factory()->create(['is_active' => true]);
    QuestStep::factory()->create(['quest_id' => $quest->id, 'step_order' => 0, 'city_id' => City::query()->value('id'), 'required_item_id' => null]);
    $user->userQuests()->create(['quest_id' => $quest->id, 'status' => 'completed', 'current_step_index' => 0]);

    $objectives = (new BuildWorldMapData)($user)['player']['objectives'];

    expect($objectives)->toBe([]);
});
