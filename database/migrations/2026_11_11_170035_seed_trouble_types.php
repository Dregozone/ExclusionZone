<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The first kinds of trouble waiting at city gates, from a hungry dog pack to a militia checkpoint.
 * Admins tune or add more on the Trouble admin page.
 */
return new class extends Migration
{
    /**
     * @var list<array{key: string, name: string, description: string, skill_key: string, fighter_level: int, damage_min: int, damage_max: int, xp_reward: int, loot_item_key: ?string, loot_quantity: int, demand_value: int}>
     */
    private array $troubleTypes = [
        [
            'key' => 'feral_dog_pack',
            'name' => 'Feral dog pack',
            'description' => 'Half-starved dogs circle the road in. Something tossed their way might buy a gap, or you can drive them off.',
            'skill_key' => 'combat_melee',
            'fighter_level' => 2,
            'damage_min' => 4,
            'damage_max' => 12,
            'xp_reward' => 30,
            'loot_item_key' => 'mutant_hide',
            'loot_quantity' => 1,
            'demand_value' => 2,
        ],
        [
            'key' => 'barricade_looters',
            'name' => 'Barricade looters',
            'description' => 'A ragged gang has dragged cars across the street and wants a toll from everyone coming through.',
            'skill_key' => 'combat_melee',
            'fighter_level' => 4,
            'damage_min' => 6,
            'damage_max' => 16,
            'xp_reward' => 45,
            'loot_item_key' => 'metal_parts',
            'loot_quantity' => 2,
            'demand_value' => 4,
        ],
        [
            'key' => 'scavenger_crew',
            'name' => 'Rival scavenger crew',
            'description' => 'A crew working this district has rifles trained on the approach and says the pickings are theirs.',
            'skill_key' => 'combat_ranged',
            'fighter_level' => 6,
            'damage_min' => 8,
            'damage_max' => 20,
            'xp_reward' => 60,
            'loot_item_key' => 'weapon_scraps',
            'loot_quantity' => 1,
            'demand_value' => 6,
        ],
        [
            'key' => 'militia_checkpoint',
            'name' => 'Militia checkpoint',
            'description' => 'Armed militia hold the only way in. They will wave through anyone who pays, and shoot at anyone who pushes.',
            'skill_key' => 'combat_ranged',
            'fighter_level' => 9,
            'damage_min' => 10,
            'damage_max' => 26,
            'xp_reward' => 90,
            'loot_item_key' => 'ammo_crates',
            'loot_quantity' => 1,
            'demand_value' => 9,
        ],
    ];

    public function up(): void
    {
        $itemIdsByKey = DB::table('items')->pluck('id', 'key');

        foreach ($this->troubleTypes as $troubleType) {
            $lootItemKey = $troubleType['loot_item_key'];
            unset($troubleType['loot_item_key']);

            DB::table('trouble_types')->updateOrInsert(['key' => $troubleType['key']], [
                ...$troubleType,
                'loot_item_id' => $lootItemKey === null ? null : $itemIdsByKey[$lootItemKey] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('trouble_types')->whereIn('key', array_column($this->troubleTypes, 'key'))->delete();
    }
};
