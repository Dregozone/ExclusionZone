<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('countries')->insert([
            ['id' => 12, 'continent' => 'Europe', 'country' => 'Denmark', 'avg_temp_c' => 9,  'rain_chance_pct' => 55, 'trouble_chance_pct' => 30, 'notes' => 'Low-lying flooded coastlines, a gateway between Scandinavia and Central Europe', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'continent' => 'Europe', 'country' => 'Norway',  'avg_temp_c' => 6,  'rain_chance_pct' => 52, 'trouble_chance_pct' => 32, 'notes' => 'Fjord city ruins and North Sea oil age remnants', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'continent' => 'Europe', 'country' => 'Sweden',  'avg_temp_c' => 7,  'rain_chance_pct' => 50, 'trouble_chance_pct' => 28, 'notes' => 'Archipelago ruins and Baltic industrial district remnants', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'continent' => 'Europe', 'country' => 'Finland', 'avg_temp_c' => 5,  'rain_chance_pct' => 48, 'trouble_chance_pct' => 35, 'notes' => 'Frozen Baltic port city, a dangerous gateway to Russian territory', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('cities')->insert([
            ['id' => 20, 'country_id' => 12, 'city' => 'Copenhagen', 'biome' => 'flooded canal city / Nordic trading ruins',   'rain_chance_pct' => 55, 'trouble_chance_pct' => 30, 'baseline_loot_tier' => 'medium',      'lat' => 55.67594, 'lng' => 12.56553, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 21, 'country_id' => 13, 'city' => 'Oslo',       'biome' => 'fjord city ruins / North Sea oil remnants',   'rain_chance_pct' => 48, 'trouble_chance_pct' => 33, 'baseline_loot_tier' => 'medium-high', 'lat' => 59.91273, 'lng' => 10.74609, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 22, 'country_id' => 14, 'city' => 'Stockholm',  'biome' => 'island archipelago ruins / Baltic trade hub',  'rain_chance_pct' => 52, 'trouble_chance_pct' => 28, 'baseline_loot_tier' => 'medium',      'lat' => 59.33258, 'lng' => 18.06490, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 23, 'country_id' => 15, 'city' => 'Helsinki',   'biome' => 'frozen Baltic port / czar-age harbour ruins',  'rain_chance_pct' => 50, 'trouble_chance_pct' => 40, 'baseline_loot_tier' => 'medium-high', 'lat' => 60.16952, 'lng' => 24.93545, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('city_connections')->insert([
            // Copenhagen <-> Gdansk (Baltic sea — connects Scandinavia to existing network)
            ['city_id' => 20, 'neighbor_city_id' => 4],  ['city_id' => 4,  'neighbor_city_id' => 20],
            // Copenhagen <-> Oslo
            ['city_id' => 20, 'neighbor_city_id' => 21], ['city_id' => 21, 'neighbor_city_id' => 20],
            // Oslo <-> Stockholm
            ['city_id' => 21, 'neighbor_city_id' => 22], ['city_id' => 22, 'neighbor_city_id' => 21],
            // Stockholm <-> Helsinki
            ['city_id' => 22, 'neighbor_city_id' => 23], ['city_id' => 23, 'neighbor_city_id' => 22],
            // Stockholm <-> Gdansk (secondary Baltic sea route)
            ['city_id' => 22, 'neighbor_city_id' => 4],  ['city_id' => 4,  'neighbor_city_id' => 22],
            // Helsinki <-> St Petersburg (Russian border crossing)
            ['city_id' => 23, 'neighbor_city_id' => 17], ['city_id' => 17, 'neighbor_city_id' => 23],
        ]);

        DB::table('city_actions')->insert([
            // Copenhagen (id: 20)
            ['city_id' => 20, 'action_key' => 'copenhagen_harbour_freight',  'label' => 'Harbour freight salvage',    'description' => 'Strip flooded shipping containers and abandoned freight terminals at the old port.',             'skill_key' => 'scavenging',   'min_level' => 4,  'risk_level' => 'medium', 'reward_profile' => json_encode(['xp' => 26, 'item_key' => 'shipping_containers', 'quantity' => 1, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 20, 'action_key' => 'copenhagen_canal_fishing',    'label' => 'Canal district fishing',     'description' => 'Fish the flooded canals and harbour approaches for reliable food and tradeable catch.',          'skill_key' => 'fishing',      'min_level' => 1,  'risk_level' => 'low',    'reward_profile' => json_encode(['xp' => 15, 'item_key' => 'fresh_fish',          'quantity' => 3, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 20, 'action_key' => 'copenhagen_nordic_barter',    'label' => 'Nordic goods trading',       'description' => 'Exchange goods with Baltic traders passing through the last functioning canal network.',          'skill_key' => 'barter',       'min_level' => 2,  'risk_level' => 'low',    'reward_profile' => json_encode(['xp' => 17, 'item_key' => 'nordic_trade_goods',  'quantity' => 2, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],

            // Oslo (id: 21)
            ['city_id' => 21, 'action_key' => 'oslo_fjord_fishing',          'label' => 'Fjord fishing run',          'description' => 'Work the deep cold fjords for salmon and sea trout, valuable food sources in the ruins.',         'skill_key' => 'fishing',      'min_level' => 1,  'risk_level' => 'low',    'reward_profile' => json_encode(['xp' => 16, 'item_key' => 'salmon_catch',        'quantity' => 2, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 21, 'action_key' => 'oslo_petroleum_reserves',     'label' => 'Petroleum reserve extraction', 'description' => 'Tap into buried North Sea pipeline infrastructure for precious fuel reserves.',                  'skill_key' => 'engineering',  'min_level' => 9,  'risk_level' => 'high',   'reward_profile' => json_encode(['xp' => 52, 'item_key' => 'fuel_drums',          'quantity' => 2, 'loot_tier' => 'high']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 21, 'action_key' => 'oslo_stave_church_ruins',     'label' => 'Stave church relic hunt',    'description' => 'Pick through the ruins of ancient timber churches for preserved artefacts and hidden caches.',   'skill_key' => 'scavenging',   'min_level' => 4,  'risk_level' => 'medium', 'reward_profile' => json_encode(['xp' => 24, 'item_key' => 'historical_relics',  'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],

            // Stockholm (id: 22)
            ['city_id' => 22, 'action_key' => 'stockholm_archipelago_trade', 'label' => 'Archipelago island trade',   'description' => 'Navigate the ruined island archipelago to reach isolated survivor communities and barter.',        'skill_key' => 'barter',       'min_level' => 2,  'risk_level' => 'low',    'reward_profile' => json_encode(['xp' => 18, 'item_key' => 'archipelago_goods',  'quantity' => 2, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 22, 'action_key' => 'stockholm_shipyard_salvage',  'label' => 'Baltic shipyard salvage',    'description' => 'Strip the dry docks and industrial shipyard ruins for heavy marine components.',                  'skill_key' => 'engineering',  'min_level' => 6,  'risk_level' => 'medium', 'reward_profile' => json_encode(['xp' => 34, 'item_key' => 'marine_parts',       'quantity' => 2, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 22, 'action_key' => 'stockholm_royal_excavation',  'label' => 'Royal palace excavation',    'description' => 'Excavate buried vaults and state rooms beneath the flooded Royal Palace island.',                  'skill_key' => 'scavenging',   'min_level' => 7,  'risk_level' => 'medium', 'reward_profile' => json_encode(['xp' => 38, 'item_key' => 'historical_relics',  'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],

            // Helsinki (id: 23)
            ['city_id' => 23, 'action_key' => 'helsinki_frozen_harbour_dive', 'label' => 'Frozen harbour dive',       'description' => 'Break ice and dive the frozen harbour for sunken cargo and submerged salvage.',                  'skill_key' => 'scavenging',   'min_level' => 7,  'risk_level' => 'high',   'reward_profile' => json_encode(['xp' => 44, 'item_key' => 'harbour_salvage',    'quantity' => 1, 'loot_tier' => 'high']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 23, 'action_key' => 'helsinki_border_smuggling',   'label' => 'Russia border smuggling',    'description' => 'Run contraband across the fortified Russian border corridor for high-risk, high-reward returns.',  'skill_key' => 'stealth',      'min_level' => 5,  'risk_level' => 'medium', 'reward_profile' => json_encode(['xp' => 30, 'item_key' => 'contraband_cache',  'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 23, 'action_key' => 'helsinki_timber_district',    'label' => 'Timber district foraging',   'description' => 'Harvest and process timber from the frost-preserved forest districts on the city edge.',          'skill_key' => 'survival',     'min_level' => 1,  'risk_level' => 'low',    'reward_profile' => json_encode(['xp' => 14, 'item_key' => 'timber_stock',       'quantity' => 3, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('city_actions')->whereIn('city_id', [20, 21, 22, 23])->delete();
        DB::table('city_connections')
            ->whereIn('city_id', [20, 21, 22, 23])
            ->orWhereIn('neighbor_city_id', [20, 21, 22, 23])
            ->delete();
        DB::table('cities')->whereIn('id', [20, 21, 22, 23])->delete();
        DB::table('countries')->whereIn('id', [12, 13, 14, 15])->delete();
    }
};
