<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('countries')->insert([
            ['id' => 7,  'continent' => 'Europe',  'country' => 'United Kingdom', 'avg_temp_c' => 10, 'rain_chance_pct' => 55, 'trouble_chance_pct' => 40, 'notes' => 'Drowned capital and ruined highland fortress cities', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8,  'continent' => 'Europe',  'country' => 'Ireland',        'avg_temp_c' => 10, 'rain_chance_pct' => 65, 'trouble_chance_pct' => 30, 'notes' => 'Isolated island survivor networks, coastal trade hubs', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9,  'continent' => 'Europe',  'country' => 'Russia',         'avg_temp_c' => 4,  'rain_chance_pct' => 38, 'trouble_chance_pct' => 75, 'notes' => 'Frozen megacity ruins, extreme danger, rare high-end salvage', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'continent' => 'Asia',    'country' => 'China',          'avg_temp_c' => 13, 'rain_chance_pct' => 42, 'trouble_chance_pct' => 65, 'notes' => 'Smog-choked megacity ruins, industrial salvage, high danger', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'continent' => 'Oceania', 'country' => 'Australia',      'avg_temp_c' => 22, 'rain_chance_pct' => 28, 'trouble_chance_pct' => 50, 'notes' => 'Sun-scorched coastal ruins, isolated from the world networks', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('cities')->insert([
            ['id' => 13, 'country_id' => 7,  'city' => 'London',        'biome' => 'drowned parliament ruins / flooded zones',    'rain_chance_pct' => 58, 'trouble_chance_pct' => 52, 'baseline_loot_tier' => 'high',        'lat' => 51.50735,  'lng' => -0.12776,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 14, 'country_id' => 7,  'city' => 'Edinburgh',     'biome' => 'ancient fortress city / highland stronghold', 'rain_chance_pct' => 62, 'trouble_chance_pct' => 35, 'baseline_loot_tier' => 'medium-high', 'lat' => 55.95325,  'lng' => -3.18883,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 15, 'country_id' => 8,  'city' => 'Dublin',        'biome' => 'coastal trade hub / Irish survivor port',     'rain_chance_pct' => 67, 'trouble_chance_pct' => 28, 'baseline_loot_tier' => 'medium',      'lat' => 53.33306,  'lng' => -6.24889,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 16, 'country_id' => 9,  'city' => 'Moscow',        'biome' => 'frozen megacity ruins / irradiated suburbs',  'rain_chance_pct' => 40, 'trouble_chance_pct' => 80, 'baseline_loot_tier' => 'rare',        'lat' => 55.75583,  'lng' => 37.61778,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 17, 'country_id' => 9,  'city' => 'St Petersburg', 'biome' => 'flooded czarist district / Baltic port ruins', 'rain_chance_pct' => 45, 'trouble_chance_pct' => 70, 'baseline_loot_tier' => 'high',        'lat' => 59.93900,  'lng' => 30.31600,  'created_at' => now(), 'updated_at' => now()],
            ['id' => 18, 'country_id' => 10, 'city' => 'Beijing',       'biome' => 'smog-choked megacity / polluted ruins',        'rain_chance_pct' => 44, 'trouble_chance_pct' => 68, 'baseline_loot_tier' => 'high',        'lat' => 39.90750,  'lng' => 116.39723, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 19, 'country_id' => 11, 'city' => 'Sydney',        'biome' => 'sun-scorched harbour ruins / coastal fortress', 'rain_chance_pct' => 30, 'trouble_chance_pct' => 48, 'baseline_loot_tier' => 'medium-high', 'lat' => -33.86785, 'lng' => 151.20732, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('city_connections')->insert([
            // Edinburgh <-> London
            ['city_id' => 13, 'neighbor_city_id' => 14], ['city_id' => 14, 'neighbor_city_id' => 13],
            // London <-> Dublin
            ['city_id' => 13, 'neighbor_city_id' => 15], ['city_id' => 15, 'neighbor_city_id' => 13],
            // London <-> Warsaw (cross-channel / European rail corridor)
            ['city_id' => 13, 'neighbor_city_id' => 3],  ['city_id' => 3,  'neighbor_city_id' => 13],
            // Gdansk <-> St Petersburg (Baltic sea route)
            ['city_id' => 4,  'neighbor_city_id' => 17], ['city_id' => 17, 'neighbor_city_id' => 4],
            // St Petersburg <-> Moscow
            ['city_id' => 17, 'neighbor_city_id' => 16], ['city_id' => 16, 'neighbor_city_id' => 17],
            // Moscow <-> Warsaw (overland Eastern Europe)
            ['city_id' => 16, 'neighbor_city_id' => 3],  ['city_id' => 3,  'neighbor_city_id' => 16],
            // Beijing <-> Tokyo (East Asia sea crossing)
            ['city_id' => 18, 'neighbor_city_id' => 7],  ['city_id' => 7,  'neighbor_city_id' => 18],
            // Sydney <-> Cape Town (Southern Ocean route)
            ['city_id' => 19, 'neighbor_city_id' => 12], ['city_id' => 12, 'neighbor_city_id' => 19],
            // Sydney <-> Beijing (Pacific maritime route)
            ['city_id' => 19, 'neighbor_city_id' => 18], ['city_id' => 18, 'neighbor_city_id' => 19],
        ]);

        DB::table('city_actions')->insert([
            // London (id: 13)
            ['city_id' => 13, 'action_key' => 'london_parliament_scavenge',    'label' => 'Drowned parliament scavenge',  'description' => 'Wade through flooded government halls for lost state archives and high-value relics.',           'skill_key' => 'scavenging',    'min_level' => 10, 'risk_level' => 'high',    'reward_profile' => json_encode(['xp' => 55, 'item_key' => 'state_documents',    'quantity' => 1, 'loot_tier' => 'high']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 13, 'action_key' => 'london_fog_district_trading',   'label' => 'Fog district trading',         'description' => 'Negotiate with surviving traders in fog-shrouded street markets above the flood line.',          'skill_key' => 'barter',        'min_level' => 1,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 18, 'item_key' => 'trade_goods',        'quantity' => 2, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 13, 'action_key' => 'london_underground_patrol',     'label' => 'Underground tunnel patrol',    'description' => 'Clear hostile squatters from flooded tube tunnels and recover buried gear caches.',               'skill_key' => 'combat_melee',  'min_level' => 6,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 32, 'item_key' => 'metro_gear',         'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],

            // Edinburgh (id: 14)
            ['city_id' => 14, 'action_key' => 'edinburgh_castle_sweep',        'label' => 'Castle ruins sweep',           'description' => 'Pick through the volcanic rock castle ruins for buried armoury caches and highland relics.',     'skill_key' => 'scavenging',    'min_level' => 5,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 28, 'item_key' => 'historical_relics',  'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 14, 'action_key' => 'edinburgh_highland_hunt',       'label' => 'Highland hunting',             'description' => 'Track red deer and feral livestock across bleak moorland and highland passes.',                 'skill_key' => 'hunting',       'min_level' => 3,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 22, 'item_key' => 'game_meat',          'quantity' => 2, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 14, 'action_key' => 'edinburgh_clan_mediation',      'label' => 'Clan mediation',               'description' => 'Broker peace between rival survivor clans to open new trade lanes and gather intel.',           'skill_key' => 'barter',        'min_level' => 1,  'risk_level' => 'low',     'reward_profile' => json_encode(['xp' => 16, 'item_key' => 'alliance_tokens',    'quantity' => 1, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],

            // Dublin (id: 15)
            ['city_id' => 15, 'action_key' => 'dublin_harbour_salvage',        'label' => 'Harbour salvage',              'description' => 'Strip rusted trawlers and cargo containers at the old Dublin port for usable parts.',           'skill_key' => 'engineering',   'min_level' => 2,  'risk_level' => 'low',     'reward_profile' => json_encode(['xp' => 17, 'item_key' => 'marine_parts',       'quantity' => 2, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 15, 'action_key' => 'dublin_coastal_fishing',        'label' => 'Coastal fishing run',          'description' => 'Cast nets in the Irish Sea for reliable food and tradeable catch.',                            'skill_key' => 'fishing',       'min_level' => 1,  'risk_level' => 'low',     'reward_profile' => json_encode(['xp' => 14, 'item_key' => 'salt_fish',          'quantity' => 3, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 15, 'action_key' => 'dublin_black_market_contacts',  'label' => 'Black-market contacts',        'description' => 'Work the underground network to move contraband and gather intelligence across the Irish Sea.',  'skill_key' => 'barter',        'min_level' => 4,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 26, 'item_key' => 'contraband_cache',   'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],

            // Moscow (id: 16)
            ['city_id' => 16, 'action_key' => 'moscow_kremlin_scavenging',     'label' => 'Kremlin district scavenging',  'description' => 'Risk the irradiated core to recover state-level hardware from buried Kremlin vaults.',          'skill_key' => 'scavenging',    'min_level' => 18, 'risk_level' => 'extreme', 'reward_profile' => json_encode(['xp' => 110, 'item_key' => 'state_archives',   'quantity' => 1, 'loot_tier' => 'rare']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 16, 'action_key' => 'moscow_frozen_metro',           'label' => 'Frozen metro tunnels',         'description' => 'Navigate ice-locked tunnels beneath the frozen city to recover buried supply caches.',          'skill_key' => 'survival',      'min_level' => 10, 'risk_level' => 'high',    'reward_profile' => json_encode(['xp' => 58, 'item_key' => 'metro_supplies',     'quantity' => 1, 'loot_tier' => 'high']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 16, 'action_key' => 'moscow_oligarch_vault',         'label' => 'Oligarch vault cracking',      'description' => 'Force open reinforced private vaults in the ruins of luxury tower blocks.',                     'skill_key' => 'engineering',   'min_level' => 14, 'risk_level' => 'high',    'reward_profile' => json_encode(['xp' => 78, 'item_key' => 'vault_contents',     'quantity' => 1, 'loot_tier' => 'rare']),        'created_at' => now(), 'updated_at' => now()],

            // St Petersburg (id: 17)
            ['city_id' => 17, 'action_key' => 'stpete_flooded_palace_salvage', 'label' => 'Flooded palace salvage',       'description' => 'Dive the submerged grand halls of the Winter Palace for imperial artefacts.',                   'skill_key' => 'scavenging',    'min_level' => 8,  'risk_level' => 'high',    'reward_profile' => json_encode(['xp' => 48, 'item_key' => 'imperial_relics',    'quantity' => 1, 'loot_tier' => 'high']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 17, 'action_key' => 'stpete_baltic_port_trading',    'label' => 'Baltic port trading',          'description' => 'Trade salvaged goods with Baltic sea-runners passing through ruined harbour docks.',            'skill_key' => 'barter',        'min_level' => 2,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 20, 'item_key' => 'shipping_manifest',  'quantity' => 1, 'loot_tier' => 'medium']),      'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 17, 'action_key' => 'stpete_hermitage_recovery',     'label' => 'Hermitage artefact recovery',  'description' => 'Infiltrate the flooded museum wings to recover cultural treasures from submerged galleries.',  'skill_key' => 'stealth',       'min_level' => 12, 'risk_level' => 'high',    'reward_profile' => json_encode(['xp' => 65, 'item_key' => 'art_relics',         'quantity' => 1, 'loot_tier' => 'rare']),        'created_at' => now(), 'updated_at' => now()],

            // Beijing (id: 18)
            ['city_id' => 18, 'action_key' => 'beijing_forbidden_city',        'label' => 'Forbidden City excavation',   'description' => 'Excavate the smog-buried imperial complex for rare cultural artefacts and hidden tech caches.', 'skill_key' => 'scavenging',    'min_level' => 12, 'risk_level' => 'high',    'reward_profile' => json_encode(['xp' => 68, 'item_key' => 'imperial_artifacts', 'quantity' => 1, 'loot_tier' => 'rare']),        'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 18, 'action_key' => 'beijing_smog_manufacturing',    'label' => 'Smog district manufacturing',  'description' => 'Operate salvaged factory lines in the toxic smog-belt industrial ruins for synthetic parts.',  'skill_key' => 'crafting',      'min_level' => 6,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 36, 'item_key' => 'synthetic_parts',    'quantity' => 2, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 18, 'action_key' => 'beijing_data_center_recovery',  'label' => 'Data centre recovery',         'description' => 'Break into buried server farms beneath the old finance district for intact storage cores.',     'skill_key' => 'engineering',   'min_level' => 15, 'risk_level' => 'extreme', 'reward_profile' => json_encode(['xp' => 95, 'item_key' => 'server_cores',       'quantity' => 1, 'loot_tier' => 'rare']),        'created_at' => now(), 'updated_at' => now()],

            // Sydney (id: 19)
            ['city_id' => 19, 'action_key' => 'sydney_harbour_bridge_salvage', 'label' => 'Harbour Bridge salvage',       'description' => 'Strip high-grade steel and hardware from the sun-blasted iconic bridge structure.',             'skill_key' => 'engineering',   'min_level' => 7,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 38, 'item_key' => 'bridge_steel',       'quantity' => 2, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 19, 'action_key' => 'sydney_outback_trade',          'label' => 'Outback survivor trade',       'description' => 'Run supply lines to inland outback survivor camps, trading coastal goods for desert finds.',   'skill_key' => 'barter',        'min_level' => 4,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 28, 'item_key' => 'desert_resources',   'quantity' => 2, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
            ['city_id' => 19, 'action_key' => 'sydney_coastal_defence',        'label' => 'Coastal defence patrol',       'description' => 'Defend the harbour fortress perimeter from seaborne raider incursions off the Pacific.',       'skill_key' => 'combat_ranged', 'min_level' => 5,  'risk_level' => 'medium',  'reward_profile' => json_encode(['xp' => 33, 'item_key' => 'tactical_gear',      'quantity' => 1, 'loot_tier' => 'medium-high']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('city_actions')->whereIn('city_id', [13, 14, 15, 16, 17, 18, 19])->delete();
        DB::table('city_connections')
            ->whereIn('city_id', [13, 14, 15, 16, 17, 18, 19])
            ->orWhereIn('neighbor_city_id', [13, 14, 15, 16, 17, 18, 19])
            ->delete();
        DB::table('cities')->whereIn('id', [13, 14, 15, 16, 17, 18, 19])->delete();
        DB::table('countries')->whereIn('id', [7, 8, 9, 10, 11])->delete();
    }
};
