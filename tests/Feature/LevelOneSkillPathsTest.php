<?php

use App\Models\CityAction;
use App\Models\Quest;
use App\Models\Skill;

test('every skill but engineering has a city action a new survivor can start', function () {
    $skillKeysWithLevelOneActions = CityAction::query()->where('min_level', 1)->distinct()->pluck('skill_key');

    expect(Skill::query()->pluck('key')->diff($skillKeysWithLevelOneActions)->values()->all())->toBe(['engineering']);
});

test('the first story quest opens engineering with scrap metal from a level one kyiv action', function () {
    $handOverStep = Quest::query()->where('name', 'Static')->firstOrFail()
        ->steps()->with('requiredItem')->where('step_order', 1)->firstOrFail();

    $sourceAction = CityAction::query()
        ->whereRelation('city', 'city', 'Kyiv')
        ->where('min_level', 1)
        ->where('reward_profile->item_key', $handOverStep->requiredItem->key)
        ->first();

    expect($handOverStep->requiredItem->key)->toBe('scrap_metal')
        ->and($handOverStep->required_item_quantity)->toBe(2)
        ->and($sourceAction?->skill_key)->toBe('scavenging');
});
