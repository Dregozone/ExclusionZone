<?php

use App\Models\CityAction;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserWork;

test('a city action shows the scene for its skill', function () {
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'shelter_repair')->firstOrFail();

    $this->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee(asset('images/work/construction.jpg'))
        ->assertDontSee('Placeholder image area');
});

test('travel shows the travel scene', function () {
    $user = User::factory()->create();
    $destination = $user->location->city->neighbors()->firstOrFail();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);

    $this->get(route('dashboard'))->assertOk()->assertSee(asset('images/work/travel.jpg'));
});

test('feeding the Brain Scorcher shows its scene', function () {
    $work = new UserWork(['work_type' => 'brain_scorcher']);

    expect($work->sceneImagePath())->toBe('images/work/brain_scorcher.jpg');
});

test('a skill without its own scene falls back to the default scene', function () {
    $work = new UserWork(['work_type' => 'city_action', 'skill_key' => 'added_by_an_admin']);

    expect($work->sceneImagePath())->toBe('images/work/default.jpg');
});

test('every seeded skill and work type has a scene image on disk', function () {
    $works = Skill::query()->pluck('key')
        ->map(fn (string $key): UserWork => new UserWork(['work_type' => 'city_action', 'skill_key' => $key]))
        ->push(new UserWork(['work_type' => 'travel']), new UserWork(['work_type' => 'brain_scorcher']));

    foreach ($works as $work) {
        expect(public_path($work->sceneImagePath()))->toBeFile();
    }

    foreach (UserWork::SKILL_SCENES as $scene) {
        expect(public_path("images/work/{$scene}.jpg"))->toBeFile();
    }

    expect(public_path('images/work/default.jpg'))->toBeFile();
});
