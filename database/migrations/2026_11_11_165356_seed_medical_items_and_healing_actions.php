<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds bandages and antiseptic, makes field medkits a medical item, and seeds the actions that
 * loot, craft or pay for healing: a Kyiv pharmacy raid, bandage rolling in Warsaw, antiseptic
 * distilling in Manaus, and field clinics in Kyiv and Warsaw that patch survivors up each cycle.
 */
return new class extends Migration
{
    /**
     * @var array<string, int>
     */
    private array $healthRestoredByMedicalItemKey = [
        'bandages' => 15,
        'antiseptic' => 25,
        'field_medkits' => 50,
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->firstOrCreateItem([
                'key' => 'bandages',
                'name' => 'Bandages',
                'description' => 'Strips of boiled linen, rolled tight. Stops the bleeding and keeps the dirt out.',
                'rarity' => 'common',
            ]);

            $this->firstOrCreateItem([
                'key' => 'antiseptic',
                'name' => 'Antiseptic',
                'description' => 'A stoppered vial of sharp-smelling tincture. Stings like fire and stops wounds turning bad.',
                'rarity' => 'uncommon',
            ]);

            foreach ($this->healthRestoredByMedicalItemKey as $itemKey => $healthRestored) {
                DB::table('items')->where('key', $itemKey)->update([
                    'is_medical' => true,
                    'health_restored' => $healthRestored,
                    'hunger_restored' => 0,
                    'fatigue_restored' => 0,
                    'updated_at' => now(),
                ]);
            }

            $this->seedActions();
        });
    }

    /**
     * The items may be held by players by now, so rolling back only removes the actions.
     */
    public function down(): void
    {
        DB::table('city_actions')->whereIn('action_key', array_column($this->actions(), 'action_key'))->delete();
    }

    private function seedActions(): void
    {
        $cityIds = DB::table('cities')->whereIn('city', ['Kyiv', 'Warsaw', 'Manaus'])->pluck('id', 'city');

        foreach ($this->actions() as $action) {
            $cityId = $cityIds[$action['city']] ?? null;

            if ($cityId === null || DB::table('city_actions')->where('action_key', $action['action_key'])->exists()) {
                continue;
            }

            unset($action['city']);

            DB::table('city_actions')->insert([
                ...$action,
                'city_id' => $cityId,
                'reward_profile' => json_encode($action['reward_profile']),
                'required_items' => $action['required_items'] === null ? null : json_encode($action['required_items']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @return list<array{city: string, action_key: string, label: string, description: string, skill_key: string, min_level: int, risk_level: string, base_duration_seconds: int, hunger_cost: int, fatigue_cost: int, reward_profile: array<string, mixed>, required_items: list<array{item_key: string, quantity: int}>|null}>
     */
    private function actions(): array
    {
        return [
            [
                'city' => 'Kyiv',
                'action_key' => 'pharmacy_raid',
                'label' => 'Pharmacy raid',
                'description' => 'Pick through looted pharmacies above the metro for bandages the last crews missed.',
                'skill_key' => 'scavenging',
                'min_level' => 2,
                'risk_level' => 'medium',
                'base_duration_seconds' => 20,
                'hunger_cost' => 3,
                'fatigue_cost' => 3,
                'reward_profile' => ['xp' => 20, 'item_key' => 'bandages', 'quantity' => 1, 'loot_tier' => 'medium'],
                'required_items' => null,
            ],
            [
                'city' => 'Kyiv',
                'action_key' => 'metro_field_clinic',
                'label' => 'Metro field clinic',
                'description' => 'Pay the station medic in ration tokens to clean and stitch your wounds.',
                'skill_key' => 'medicine',
                'min_level' => 1,
                'risk_level' => 'low',
                'base_duration_seconds' => 20,
                'hunger_cost' => 1,
                'fatigue_cost' => 0,
                'reward_profile' => ['xp' => 6, 'health' => 15, 'loot_tier' => 'low'],
                'required_items' => [['item_key' => 'ration_tokens', 'quantity' => 2]],
            ],
            [
                'city' => 'Warsaw',
                'action_key' => 'bandage_rolling',
                'label' => 'Bandage rolling',
                'description' => 'Boil salvaged linen in clean water and roll it into bandages for the garrison.',
                'skill_key' => 'medicine',
                'min_level' => 1,
                'risk_level' => 'low',
                'base_duration_seconds' => 15,
                'hunger_cost' => 2,
                'fatigue_cost' => 2,
                'reward_profile' => ['xp' => 16, 'item_key' => 'bandages', 'quantity' => 2, 'loot_tier' => 'medium'],
                'required_items' => [['item_key' => 'clean_water', 'quantity' => 2]],
            ],
            [
                'city' => 'Warsaw',
                'action_key' => 'garrison_field_medic',
                'label' => 'Garrison field medic',
                'description' => 'Trade a sealed ration for an hour on the garrison medic’s cot.',
                'skill_key' => 'medicine',
                'min_level' => 1,
                'risk_level' => 'low',
                'base_duration_seconds' => 20,
                'hunger_cost' => 1,
                'fatigue_cost' => 0,
                'reward_profile' => ['xp' => 6, 'health' => 20, 'loot_tier' => 'low'],
                'required_items' => [['item_key' => 'sealed_rations', 'quantity' => 1]],
            ],
            [
                'city' => 'Manaus',
                'action_key' => 'antiseptic_distilling',
                'label' => 'Antiseptic distilling',
                'description' => 'Steep jungle herbs in clean water and distil a tincture that keeps wounds clean.',
                'skill_key' => 'medicine',
                'min_level' => 3,
                'risk_level' => 'low',
                'base_duration_seconds' => 20,
                'hunger_cost' => 2,
                'fatigue_cost' => 2,
                'reward_profile' => ['xp' => 24, 'item_key' => 'antiseptic', 'quantity' => 2, 'loot_tier' => 'medium-high'],
                'required_items' => [['item_key' => 'rare_herbs', 'quantity' => 1], ['item_key' => 'clean_water', 'quantity' => 1]],
            ],
        ];
    }

    /**
     * @param  array{key: string, name: string, description: string, rarity: string}  $item
     */
    private function firstOrCreateItem(array $item): void
    {
        if (DB::table('items')->where('key', $item['key'])->exists()) {
            return;
        }

        DB::table('items')->insert([...$item, 'created_at' => now(), 'updated_at' => now()]);
    }
};
