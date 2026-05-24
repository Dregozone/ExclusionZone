<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('skills')->insert([
            ['id' => 1, 'key' => 'scavenging', 'display_name' => 'Scavenging', 'description' => 'Search ruins, vehicles, bunkers', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'key' => 'cooking', 'display_name' => 'Cooking', 'description' => 'Prepare food and buffs', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'key' => 'fishing', 'display_name' => 'Fishing', 'description' => 'Catch fish and salvage from water', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'key' => 'hunting', 'display_name' => 'Hunting', 'description' => 'Track and kill wildlife/hostiles', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'key' => 'crafting', 'display_name' => 'Crafting', 'description' => 'Build tools, weapons, components', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 6, 'key' => 'construction', 'display_name' => 'Construction', 'description' => 'Build/repair shelters and defenses', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'key' => 'combat_melee', 'display_name' => 'Melee Combat', 'description' => 'Close-range combat', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'key' => 'combat_ranged', 'display_name' => 'Ranged Combat', 'description' => 'Firearms/ranged weapons', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 9, 'key' => 'medicine', 'display_name' => 'Medicine', 'description' => 'Heal and craft medkits', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 10, 'key' => 'engineering', 'display_name' => 'Engineering', 'description' => 'Power systems, traps, advanced gear', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 11, 'key' => 'barter', 'display_name' => 'Barter', 'description' => 'Better trade pricing', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'key' => 'stealth', 'display_name' => 'Stealth', 'description' => 'Avoid detection and ambush', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'key' => 'survival', 'display_name' => 'Survival', 'description' => 'Resource efficiency and hazard resistance', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('skill_level_rules')->insert([
            ['tier' => 'novice', 'level_min' => 1, 'level_max' => 9, 'unlock_examples' => 'basic gathering and weak hunts', 'created_at' => now(), 'updated_at' => now()],
            ['tier' => 'trained', 'level_min' => 10, 'level_max' => 24, 'unlock_examples' => 'improved yields and mid-tier recipes', 'created_at' => now(), 'updated_at' => now()],
            ['tier' => 'veteran', 'level_min' => 25, 'level_max' => 49, 'unlock_examples' => 'advanced gear and dangerous zones', 'created_at' => now(), 'updated_at' => now()],
            ['tier' => 'expert', 'level_min' => 50, 'level_max' => null, 'unlock_examples' => 'elite hunts and rare crafting', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('premium_cosmetics')->insert([
            ['id' => 1, 'cosmetic_type' => 'outfit_skin', 'name' => 'Wasteland Ranger Set', 'gameplay_bonus' => 'none', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'cosmetic_type' => 'outfit_skin', 'name' => 'Neon Hazmat Variant', 'gameplay_bonus' => 'none', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'cosmetic_type' => 'ui_theme', 'name' => 'Retro CRT Theme', 'gameplay_bonus' => 'none', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'cosmetic_type' => 'ui_theme', 'name' => 'Dark Ash Theme', 'gameplay_bonus' => 'none', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'cosmetic_type' => 'profile_flair', 'name' => 'Animated Nameplate', 'gameplay_bonus' => 'none', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('items')->insert([
            ['key' => 'rare_components', 'name' => 'Rare Components', 'description' => 'High-value reactor parts and sealed modules.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'irradiated_samples', 'name' => 'Irradiated Samples', 'description' => 'Scientific residue collected from hot zones.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mutant_hide', 'name' => 'Mutant Hide', 'description' => 'Tough biological salvage from hostile creatures.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'scrap_metal', 'name' => 'Scrap Metal', 'description' => 'Common salvage suitable for repairs and trade.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'treated_lumber', 'name' => 'Treated Lumber', 'description' => 'Reusable beams from shelter projects.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ration_tokens', 'name' => 'Ration Tokens', 'description' => 'Barter slips redeemable in secure markets.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'reinforced_brackets', 'name' => 'Reinforced Brackets', 'description' => 'Construction hardware for major defenses.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'sealed_rations', 'name' => 'Sealed Rations', 'description' => 'Shelf-stable meals prepared for long runs.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ammo_crates', 'name' => 'Ammo Crates', 'description' => 'Packed ammunition lots ready for shipment.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'salt_fish', 'name' => 'Salt Fish', 'description' => 'Processed fish useful for trade or survival.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'marine_parts', 'name' => 'Marine Parts', 'description' => 'Ship-grade components from coastal salvage.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'contraband_cache', 'name' => 'Contraband Cache', 'description' => 'Illegal goods seized on the waterfront.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'metal_parts', 'name' => 'Metal Parts', 'description' => 'Useful industrial salvage stripped from factory ruins.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'weapon_scraps', 'name' => 'Weapon Scraps', 'description' => 'Barrels, springs, and frames for dangerous builds.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'armor_plates', 'name' => 'Armor Plates', 'description' => 'Heavy salvaged plating for armor and convoy work.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'clean_water', 'name' => 'Clean Water', 'description' => 'Purified water ready for settlements and trade.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'trade_ledger', 'name' => 'Trade Ledger', 'description' => 'Documents that improve deal visibility.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'barrel_fish_oil', 'name' => 'Barrel Fish Oil', 'description' => 'Dense fuel and trade stock from deep-sea catches.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'drone_parts', 'name' => 'Drone Parts', 'description' => 'Tokyo-grade tech salvage.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'circuit_boards', 'name' => 'Circuit Boards', 'description' => 'Recovered electronics for advanced builds.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'secure_data', 'name' => 'Secure Data', 'description' => 'Encrypted sector data worth real money.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'winter_game', 'name' => 'Winter Game', 'description' => 'Cold-weather hunted meat and hides.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'fuel_cells', 'name' => 'Fuel Cells', 'description' => 'High-value fuel reserves for frontier use.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'insulated_panels', 'name' => 'Insulated Panels', 'description' => 'Frontier shelter parts for cold cities.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'foraged_produce', 'name' => 'Foraged Produce', 'description' => 'Urban-grown food gathered in dense districts.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'faction_tokens', 'name' => 'Faction Tokens', 'description' => 'Proof of influence across unstable districts.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'fresh_vegetables', 'name' => 'Fresh Vegetables', 'description' => 'Rare fresh food from rooftop farms.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'rare_herbs', 'name' => 'Rare Herbs', 'description' => 'Potent jungle plants gathered from the danger line.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'field_medkits', 'name' => 'Field Medkits', 'description' => 'Portable medicine built for rough travel.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'river_goods', 'name' => 'River Goods', 'description' => 'Cargo lots moved between jungle settlements.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'ore_concentrate', 'name' => 'Ore Concentrate', 'description' => 'Processed mineral salvage from mine runs.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'convoy_supplies', 'name' => 'Convoy Supplies', 'description' => 'Recovered supply packs from defended routes.', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'coastal_grain', 'name' => 'Coastal Grain', 'description' => 'Wind-hardy crops from shoreline farming.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('items')->delete();
        DB::table('premium_cosmetics')->delete();
        DB::table('skill_level_rules')->delete();
        DB::table('skills')->delete();
    }
};
