<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Item;
use App\Models\Quest;
use App\Models\Skill;
use Illuminate\Database\Seeder;

class RecurringJobsSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::all()->keyBy('city');
        $items = Item::all()->keyBy('key');
        $skills = Skill::all()->keyBy('key');

        // Job 1: Supply Run — Kyiv
        $job1 = Quest::firstOrCreate(
            ['name' => 'Supply Run'],
            [
                'description' => 'Courier Masha runs the last functioning supply network in Kyiv\'s inner district. She always needs reliable runners for deliveries — no questions asked, reasonable rate.',
                'quest_type' => 'job',
                'is_repeatable' => true,
                'is_active' => true,
                'reward_skill_id' => $skills['barter']->id,
                'reward_xp_amount' => 500,
            ],
        );
        $job1->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Courier Masha',
            'action_label' => 'Pick up the supply manifest from Masha',
            'interaction_text' => 'You again. Good. I\'ve got a shipment that needs moving — don\'t ask what\'s in it, don\'t ask where it\'s going. Usual rate. Bring me what\'s on the list and we\'ll call it done.',
        ]);
        $job1->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Kyiv']->id,
            'person_of_interest' => 'Courier Masha',
            'action_label' => 'Deliver the supplies to Masha',
            'interaction_text' => 'That\'s exactly what I needed. You\'re reliable — I respect that. Come back when you need more work.',
            'requirement_variants' => [
                ['required_item_id' => $items['sealed_rations']->id, 'required_item_quantity' => 2],
                ['required_item_id' => $items['clean_water']->id, 'required_item_quantity' => 3],
                ['required_item_id' => $items['ration_tokens']->id, 'required_item_quantity' => 3],
                ['required_item_id' => $items['scrap_metal']->id, 'required_item_quantity' => 3],
            ],
            'consumes_item' => true,
        ]);

        // Job 2: Zone Salvage Contract — Pripyat
        $job2 = Quest::firstOrCreate(
            ['name' => 'Zone Salvage Contract'],
            [
                'description' => 'Trader Pavel operates a lucrative black market in Zone-sourced materials. He\'s always buying — and he pays well for the right salvage.',
                'quest_type' => 'job',
                'is_repeatable' => true,
                'is_active' => true,
                'reward_skill_id' => $skills['scavenging']->id,
                'reward_xp_amount' => 500,
            ],
        );
        $job2->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Pripyat']->id,
            'person_of_interest' => 'Trader Pavel',
            'action_label' => 'Get the salvage contract from Pavel',
            'interaction_text' => 'Got a buyer on the outside who pays premium for Zone material — certified samples, mutant derivatives, hot components. One run, clean handoff. You look like someone who knows the Zone. Interested?',
        ]);
        $job2->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Pripyat']->id,
            'person_of_interest' => 'Trader Pavel',
            'action_label' => 'Deliver the salvage to Pavel',
            'interaction_text' => 'Clean delivery. The buyer won\'t complain. There\'s another contract when you\'re ready — same terms.',
            'requirement_variants' => [
                ['required_item_id' => $items['irradiated_samples']->id, 'required_item_quantity' => 1],
                ['required_item_id' => $items['rare_components']->id, 'required_item_quantity' => 1],
                ['required_item_id' => $items['mutant_hide']->id, 'required_item_quantity' => 2],
                ['required_item_id' => $items['weapon_scraps']->id, 'required_item_quantity' => 2],
            ],
            'consumes_item' => true,
        ]);

        // Job 3: Field Medic Run — Warsaw
        $job3 = Quest::firstOrCreate(
            ['name' => 'Field Medic Run'],
            [
                'description' => 'Dr. Nowak coordinates medical supply distribution across Warsaw\'s survivor districts. She\'s chronically short on supplies and pays what she can for reliable deliveries.',
                'quest_type' => 'job',
                'is_repeatable' => true,
                'is_active' => true,
                'reward_skill_id' => $skills['medicine']->id,
                'reward_xp_amount' => 500,
            ],
        );
        $job3->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Warsaw']->id,
            'person_of_interest' => 'Dr. Nowak',
            'action_label' => 'Get the medical supply assignment from Dr. Nowak',
            'interaction_text' => 'Three districts are running low and I can\'t source fast enough through normal channels. I need whatever you can carry — the list changes every run depending on what\'s critical. Check back in when you\'ve got supplies and I\'ll tell you what\'s most urgent.',
        ]);
        $job3->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Warsaw']->id,
            'person_of_interest' => 'Dr. Nowak',
            'action_label' => 'Deliver medical supplies to Dr. Nowak',
            'interaction_text' => 'This will keep three families going another week. Thank you. Come back soon — it never stops being critical.',
            'requirement_variants' => [
                ['required_item_id' => $items['bandage']->id, 'required_item_quantity' => 3],
                ['required_item_id' => $items['field_medkits']->id, 'required_item_quantity' => 1],
                ['required_item_id' => $items['clean_water']->id, 'required_item_quantity' => 4],
                ['required_item_id' => $items['sealed_rations']->id, 'required_item_quantity' => 2],
            ],
            'consumes_item' => true,
        ]);

        // Job 4: Intel Broker — Moscow
        $job4 = Quest::firstOrCreate(
            ['name' => 'Intel Broker'],
            [
                'description' => 'A mysterious figure known only as "The Owl" collects intelligence from across the dead network. The source of his funding is unknown. His rates are not.',
                'quest_type' => 'job',
                'is_repeatable' => true,
                'is_active' => true,
                'reward_skill_id' => $skills['stealth']->id,
                'reward_xp_amount' => 500,
            ],
        );
        $job4->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Moscow']->id,
            'person_of_interest' => 'The Owl',
            'action_label' => 'Pick up the intelligence assignment from The Owl',
            'interaction_text' => 'Information has value. The right document, the right data package — worth more than food in the right hands. I always have a buyer. My requirements shift with the market. Bring me what\'s on the list and ask no questions. That\'s the arrangement.',
        ]);
        $job4->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Moscow']->id,
            'person_of_interest' => 'The Owl',
            'action_label' => 'Deliver the intelligence package to The Owl',
            'interaction_text' => 'Clean. Uncompromised. My buyer will be satisfied. The arrangement continues.',
            'requirement_variants' => [
                ['required_item_id' => $items['secure_data']->id, 'required_item_quantity' => 1],
                ['required_item_id' => $items['state_archives']->id, 'required_item_quantity' => 1],
                ['required_item_id' => $items['trade_ledger']->id, 'required_item_quantity' => 2],
                ['required_item_id' => $items['faction_tokens']->id, 'required_item_quantity' => 2],
            ],
            'consumes_item' => true,
        ]);

        // Job 5: Tech Salvage Contract — Tokyo
        $job5 = Quest::firstOrCreate(
            ['name' => 'Tech Salvage Contract'],
            [
                'description' => 'Merchant Kenji deals in pre-war electronics and tech components. He sources for clients across Asia and pays premium rates for clean, working parts.',
                'quest_type' => 'job',
                'is_repeatable' => true,
                'is_active' => true,
                'reward_skill_id' => $skills['engineering']->id,
                'reward_xp_amount' => 500,
            ],
        );
        $job5->steps()->firstOrCreate(['step_order' => 0], [
            'city_id' => $cities['Tokyo']->id,
            'person_of_interest' => 'Merchant Kenji',
            'action_label' => 'Get the salvage contract from Kenji',
            'interaction_text' => 'My clients are specific. They want working components, not scrap. Drone parts, circuit boards, server cores — if it processes data, I have a buyer. The list changes. Bring me whatever\'s current and I\'ll make it worth your time.',
        ]);
        $job5->steps()->firstOrCreate(['step_order' => 1], [
            'city_id' => $cities['Tokyo']->id,
            'person_of_interest' => 'Merchant Kenji',
            'action_label' => 'Deliver tech components to Kenji',
            'interaction_text' => 'These are good. My clients will be pleased. Come back for the next contract when you\'re ready.',
            'requirement_variants' => [
                ['required_item_id' => $items['drone_parts']->id, 'required_item_quantity' => 2],
                ['required_item_id' => $items['circuit_boards']->id, 'required_item_quantity' => 2],
                ['required_item_id' => $items['server_cores']->id, 'required_item_quantity' => 1],
                ['required_item_id' => $items['secure_data']->id, 'required_item_quantity' => 1],
            ],
            'consumes_item' => true,
        ]);
    }
}
