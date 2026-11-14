<?php

use App\Actions\Game\StudyAtLibrary;
use App\Models\City;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use App\Weather;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

function libraryPlayerIn(string $cityName): User
{
    $user = User::factory()->create();
    $city = City::query()->where('city', $cityName)->firstOrFail();
    $city->update(['weather' => Weather::Clear]);

    $user->location()->update([
        'country_id' => $city->country_id,
        'city_id' => $city->id,
    ]);

    return $user;
}

function giveLibraryPayment(User $user, string $itemKey, int $quantity): Item
{
    $item = Item::query()->where('key', $itemKey)->firstOrFail();
    $user->inventoryItems()->create(['item_id' => $item->id, 'quantity' => $quantity]);

    return $item;
}

function libraryCarriedQuantity(User $user, Item $item): int
{
    return (int) $user->fresh()->inventoryItems()->where('item_id', $item->id)->value('quantity');
}

function startLesson(User $user, Skill $skill, Item $item, int $quantity): TestResponse
{
    return test()->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('library.store'), ['skill_id' => $skill->id, 'item_id' => $item->id, 'quantity' => $quantity]);
}

afterEach(function () {
    Carbon::setTestNow();
});

test('a lesson takes six seconds per XP and keeps the payment in the pack until it ends', function () {
    $user = libraryPlayerIn('Gdansk');
    $water = giveLibraryPayment($user, 'clean_water', 10);
    $engineering = Skill::query()->where('key', 'engineering')->firstOrFail();

    startLesson($user, $engineering, $water, 4)->assertRedirect(route('dashboard'));

    $work = $user->fresh()->activeWork;

    expect($work->work_type)->toBe('lesson')
        ->and($work->skill_key)->toBe('engineering')
        ->and($work->offered_quantity)->toBe(4)
        ->and($work->duration_seconds)->toBe(20 * StudyAtLibrary::SECONDS_PER_XP)
        ->and(libraryCarriedQuantity($user, $water))->toBe(10);
});

test('finishing a lesson takes the payment and teaches XP by the goods barter value', function () {
    $user = libraryPlayerIn('Gdansk');
    $water = giveLibraryPayment($user, 'clean_water', 25);
    $engineering = Skill::query()->where('key', 'engineering')->firstOrFail();

    startLesson($user, $engineering, $water, 22);
    Carbon::setTestNow(now()->addHours(2));

    $this->actingAs($user)
        ->post(route('work.complete'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', 'Lesson finished. You paid 22x Clean Water, gained 110 Engineering XP and reached Engineering level 2.');

    $skillProgress = $user->fresh()->skills()->where('skill_id', $engineering->id)->first();

    expect(libraryCarriedQuantity($user, $water))->toBe(3)
        ->and($skillProgress->level)->toBe(2)
        ->and($skillProgress->xp)->toBe(10)
        ->and($user->fresh()->activeWork)->toBeNull();
});

test('a lesson teaches nothing when the payment is no longer carried', function () {
    $user = libraryPlayerIn('Gdansk');
    $water = giveLibraryPayment($user, 'clean_water', 5);
    $hunting = Skill::query()->where('key', 'hunting')->firstOrFail();

    startLesson($user, $hunting, $water, 5);
    $user->inventoryItems()->where('item_id', $water->id)->update(['quantity' => 2]);
    Carbon::setTestNow(now()->addHours(2));

    $this->actingAs($user)
        ->post(route('work.complete'))
        ->assertSessionHas('status', 'The librarian closed the book: you no longer have 5x Clean Water. Nothing was paid or learned.');

    expect(libraryCarriedQuantity($user, $water))->toBe(2)
        ->and((int) $user->fresh()->skills()->where('skill_id', $hunting->id)->value('xp'))->toBe(0);
});

test('canceling a lesson keeps the payment and teaches nothing', function () {
    $user = libraryPlayerIn('Gdansk');
    $water = giveLibraryPayment($user, 'clean_water', 5);
    $stealth = Skill::query()->where('key', 'stealth')->firstOrFail();

    startLesson($user, $stealth, $water, 5);

    $this->actingAs($user)
        ->post(route('work.cancel'))
        ->assertSessionHas('status', 'Lesson abandoned. Nothing was paid or learned.');

    expect(libraryCarriedQuantity($user, $water))->toBe(5)
        ->and($user->fresh()->activeWork)->toBeNull();
});

test('lessons are refused outside gdansk, for quest items and beyond the lesson limit', function (string $cityName, string $itemKey, int $quantity, string $message) {
    $user = libraryPlayerIn($cityName);
    $item = giveLibraryPayment($user, $itemKey, $quantity);
    $item->update(['is_quest_item' => $itemKey === 'signal_booster']);
    $crafting = Skill::query()->where('key', 'crafting')->firstOrFail();

    startLesson($user, $crafting, $item, $quantity)
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('toast', fn (array $toast): bool => $toast['text'] === $message);

    expect($user->fresh()->activeWork)->toBeNull();
})->with([
    'outside gdansk' => ['Kyiv', 'clean_water', 1, 'The library is only found in Gdansk.'],
    'a quest item' => ['Gdansk', 'signal_booster', 1, 'The librarian will not take Signal Booster. Keep it for the job it is meant for.'],
    'too much for one lesson' => ['Gdansk', 'clean_water', 101, 'One lesson teaches at most 500 XP. Pay with fewer items.'],
]);

test('the library panel only shows in gdansk and leaves quest items out of the payments', function () {
    $user = libraryPlayerIn('Gdansk');
    giveLibraryPayment($user, 'clean_water', 3);
    giveLibraryPayment($user, 'signal_booster', 1)->update(['is_quest_item' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-test="library"', false)
        ->assertViewHas('library', fn (array $library): bool => collect($library['payments'])->pluck('name')->all() === ['Clean Water']
            && count($library['skills']) === Skill::query()->count());

    $this->actingAs(libraryPlayerIn('Kyiv'))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('data-test="library"', false);
});
