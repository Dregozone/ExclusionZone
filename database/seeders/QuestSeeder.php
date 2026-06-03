<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class QuestSeeder extends Seeder
{
    public function run(): void
    {
        $bandage = Item::query()->firstOrCreate(
            ['key' => 'bandage'],
            ['name' => 'Bandage', 'description' => 'A clean cloth bandage. Essential for treating minor wounds in the field.'],
        );

        $barteringSkill = Skill::query()->where('key', 'barter')->first();
        $pripyat = City::query()->where('city', 'Pripyat')->first();

        if ($pripyat === null) {
            $this->command->warn('Pripyat city not found — quest steps will have no city assigned. Run this seeder after world data is seeded.');

            return;
        }

        $quest = Quest::query()->firstOrCreate(
            ['name' => 'Help a local'],
            [
                'description' => 'A local man in Pripyat is in desperate need of medical supplies. Travel to Pripyat and speak with him to learn what he needs.',
                'reward_skill_id' => $barteringSkill?->id,
                'reward_xp_amount' => 1000,
                'is_active' => true,
            ],
        );

        if ($quest->wasRecentlyCreated) {
            $quest->steps()->createMany([
                [
                    'step_order' => 0,
                    'city_id' => $pripyat->id,
                    'person_of_interest' => 'Local Man',
                    'action_label' => 'Speak to local man',
                    'interaction_text' => 'Please, friend — my brother was injured in an accident near the plant. I need a bandage to treat his wound but I have nothing left to trade. Can you find one and bring it back to me?',
                    'required_item_id' => null,
                    'required_item_quantity' => 1,
                    'consumes_item' => false,
                ],
                [
                    'step_order' => 1,
                    'city_id' => $pripyat->id,
                    'person_of_interest' => 'Local Man',
                    'action_label' => 'Give bandage to local man',
                    'interaction_text' => 'You found one! Thank you, survivor. My brother will live because of you. I have nothing to offer but my gratitude — and a few tips about how to trade in this region.',
                    'required_item_id' => $bandage->id,
                    'required_item_quantity' => 1,
                    'consumes_item' => true,
                ],
            ]);
        }
    }
}
