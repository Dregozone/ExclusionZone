<?php

use App\Livewire\Admin\GiveItem;
use App\Livewire\Admin\GiveSkillExperience;
use App\Models\Item;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

test('non-admins cannot open the give screens', function (string $routeName) {
    $player = User::factory()->create();

    $this->actingAs($player)->get(route($routeName))->assertForbidden();
})->with(['admin.give-item', 'admin.give-skill-experience']);

test('admins can open the give screens', function (string $routeName) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route($routeName))->assertOk();
})->with(['admin.give-item', 'admin.give-skill-experience']);

test('admin can give a player an item they do not own yet', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $item = Item::factory()->create();

    Livewire::actingAs($admin)
        ->test(GiveItem::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedItemId', $item->id)
        ->set('quantity', 5)
        ->call('giveItem')
        ->assertHasNoErrors()
        ->assertSet('selectedUserId', null);

    expect($player->inventoryItems()->where('item_id', $item->id)->value('quantity'))->toBe(5);
});

test('giving an item adds to the stack the player already holds', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $item = Item::factory()->create();
    $player->inventoryItems()->create(['item_id' => $item->id, 'quantity' => 3]);

    Livewire::actingAs($admin)
        ->test(GiveItem::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedItemId', $item->id)
        ->set('quantity', 4)
        ->call('giveItem')
        ->assertHasNoErrors();

    expect($player->inventoryItems()->where('item_id', $item->id)->sole()->quantity)->toBe(7);
});

test('admin cannot give a deleted item', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $item = Item::factory()->create();
    $item->delete();

    Livewire::actingAs($admin)
        ->test(GiveItem::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedItemId', $item->id)
        ->call('giveItem')
        ->assertHasErrors(['selectedItemId' => 'exists']);

    expect($player->inventoryItems()->where('item_id', $item->id)->exists())->toBeFalse();
});

test('admin cannot give fewer than one item', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $item = Item::factory()->create();

    Livewire::actingAs($admin)
        ->test(GiveItem::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedItemId', $item->id)
        ->set('quantity', 0)
        ->call('giveItem')
        ->assertHasErrors(['quantity' => 'min']);

    expect($player->inventoryItems()->where('item_id', $item->id)->exists())->toBeFalse();
});

test('admin giving skill experience levels the player up as far as it allows', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $skill = Skill::query()->firstOrFail();
    $player->skills()->updateOrCreate(['skill_id' => $skill->id], ['level' => 1, 'xp' => 50]);

    Livewire::actingAs($admin)
        ->test(GiveSkillExperience::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedSkillId', $skill->id)
        ->set('experience', 260)
        ->call('giveExperience')
        ->assertHasNoErrors()
        ->assertSet('experience', null);

    // 50 + 260 = 310: level 1 → 2 costs 100, level 2 → 3 costs 200, leaving 10.
    $progress = $player->skills()->where('skill_id', $skill->id)->sole();

    expect($progress->level)->toBe(3)
        ->and($progress->xp)->toBe(10);
});

test('admin can give experience in a skill the player has no progress row for', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $skill = Skill::query()->create(['key' => 'salvage_diving', 'display_name' => 'Salvage Diving', 'description' => 'Diving for scrap.']);

    Livewire::actingAs($admin)
        ->test(GiveSkillExperience::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedSkillId', $skill->id)
        ->set('experience', 40)
        ->call('giveExperience')
        ->assertHasNoErrors();

    $progress = $player->skills()->where('skill_id', $skill->id)->sole();

    expect($progress->level)->toBe(1)
        ->and($progress->xp)->toBe(40);
});

test('admin cannot give experience in a deleted skill', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $skill = Skill::query()->firstOrFail();
    $skill->delete();

    Livewire::actingAs($admin)
        ->test(GiveSkillExperience::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedSkillId', $skill->id)
        ->set('experience', 40)
        ->call('giveExperience')
        ->assertHasErrors(['selectedSkillId' => 'exists']);
});

test('admin cannot give less than one experience', function () {
    $admin = User::factory()->admin()->create();
    $player = User::factory()->create();
    $skill = Skill::query()->firstOrFail();

    Livewire::actingAs($admin)
        ->test(GiveSkillExperience::class)
        ->set('selectedUserId', $player->id)
        ->set('selectedSkillId', $skill->id)
        ->set('experience', 0)
        ->call('giveExperience')
        ->assertHasErrors(['experience' => 'min']);
});
