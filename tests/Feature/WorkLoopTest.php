<?php

use App\Models\CityAction;
use App\Models\Item;
use App\Models\User;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

/**
 * @return array{0: User, 1: CityAction, 2: int}
 */
function startWorkLoop(): array
{
    $user = User::factory()->create();
    $action = CityAction::query()->where('action_key', 'district_patrols')->firstOrFail();
    $action->update(['base_duration_seconds' => 100, 'reward_profile' => ['xp' => 1, 'item_key' => null]]);

    test()->actingAs($user)->post(route('city-action.store'), ['city_action_id' => $action->id]);

    return [$user, $action, $user->fresh('activeWork')->activeWork->duration_seconds];
}

test('a city action pays out and restarts its timer without the player acting', function () {
    [$user, $action, $duration] = startWorkLoop();
    $firstDeadline = $user->fresh('activeWork')->activeWork->available_at;

    $this->travel($duration + 1)->seconds();
    $this->artisan('work:advance-loops')->assertSuccessful();

    $work = $user->fresh('activeWork')->activeWork;

    expect($work->cycles_completed)->toBe(1)
        ->and($work->xp_earned)->toBe(1)
        ->and($work->available_at->equalTo($firstDeadline->addSeconds($work->duration_seconds)))->toBeTrue()
        ->and($user->fresh()->skillFor($action->skill_key)->xp)->toBe(1);
});

test('returning to the dashboard settles every cycle that finished while away', function () {
    [$user, $action, $duration] = startWorkLoop();

    $this->travel($duration * 3 + 1)->seconds();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('3 cycles completed');

    expect($user->fresh()->skillFor($action->skill_key)->xp)->toBe(3);
});

test('a settled cycle is not paid twice', function () {
    [$user, $action, $duration] = startWorkLoop();

    $this->travel($duration + 1)->seconds();
    $this->artisan('work:advance-loops');
    $this->artisan('work:advance-loops');

    expect($user->fresh()->skillFor($action->skill_key)->xp)->toBe(1);
});

test('stopping pays out finished cycles and forfeits the unfinished one', function () {
    [$user, $action, $duration] = startWorkLoop();

    $this->travel($duration + 1)->seconds();

    $this->actingAs($user)->post(route('work.cancel'))
        ->assertSessionHas('status', 'Work stopped after 1 cycle (+1 XP). The unfinished cycle earned nothing.');

    expect($user->fresh()->skillFor($action->skill_key)->xp)->toBe(1);
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
});

test('the loop ends with a report when the required items run out', function () {
    [$user, $action, $duration] = startWorkLoop();
    $item = Item::query()->firstOrFail();
    $action->update(['required_items' => [['item_key' => $item->key, 'quantity' => 1]]]);
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => 2]);

    $this->travel($duration * 5)->seconds();
    $this->artisan('work:advance-loops');

    expect($user->inventoryItems()->where('item_id', $item->id)->exists())->toBeFalse();
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);

    $this->actingAs($user->fresh())->get(route('dashboard'))
        ->assertSee("{$action->label} stopped after 2 cycles (+2 XP). You need 1x {$item->name} to do that.");
});

test('the loop ends at the time limit and starting new work clears the report', function () {
    [$user, $action, $duration] = startWorkLoop();
    $action->update(['reward_profile' => ['xp' => 0, 'item_key' => null]]);

    $this->travel(30)->days();
    $this->artisan('work:advance-loops');

    $cyclesInLimit = (int) floor(8 * 3600 / $duration);

    expect($user->fresh()->last_work_report)->toContain("after {$cyclesInLimit} cycles")
        ->toContain('8 hour limit');
    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);

    $this->actingAs($user->fresh())->post(route('city-action.store'), ['city_action_id' => $action->id]);

    expect($user->fresh()->last_work_report)->toBeNull();
});

test('the loop ends when the player loses access to city actions', function () {
    [$user, , $duration] = startWorkLoop();
    $user->role->tasks()->detach();

    $this->travel($duration + 1)->seconds();
    $this->artisan('work:advance-loops');

    $this->assertDatabaseMissing('user_works', ['user_id' => $user->id]);
    expect($user->fresh()->last_work_report)->toContain('stopped after 1 cycle');
});

test('work loops are settled every minute by the scheduler', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains($event->command, 'work:advance-loops'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *');
});

test('travel is not repeated by the loop', function () {
    $user = User::factory()->create();
    $destination = $user->location->city->neighbors()->firstOrFail();

    $this->actingAs($user)->post(route('travel.store'), ['city_id' => $destination->id]);

    $this->travel(1)->days();
    $this->artisan('work:advance-loops');

    $this->assertDatabaseHas('user_works', ['user_id' => $user->id, 'work_type' => 'travel']);
    expect($user->fresh('location')->location->city_id)->not->toBe($destination->id);
});
