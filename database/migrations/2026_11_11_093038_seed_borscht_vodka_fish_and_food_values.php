<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds Borscht and Vodka Fish, cooked in Kyiv, gives the food already in the world a value
 * when eaten, and makes riskier city actions cost more hunger and fatigue per cycle.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{hunger_restored: int, fatigue_restored: int, health_restored: int}>
     */
    private array $foodValuesByItemKey = [
        'borscht' => ['hunger_restored' => 45, 'fatigue_restored' => 5, 'health_restored' => 15],
        'vodka_fish' => ['hunger_restored' => 25, 'fatigue_restored' => 20, 'health_restored' => 5],
        'sealed_rations' => ['hunger_restored' => 30, 'fatigue_restored' => 0, 'health_restored' => 5],
        'salt_fish' => ['hunger_restored' => 15, 'fatigue_restored' => 0, 'health_restored' => 0],
        'fresh_vegetables' => ['hunger_restored' => 10, 'fatigue_restored' => 0, 'health_restored' => 5],
        'clean_water' => ['hunger_restored' => 0, 'fatigue_restored' => 10, 'health_restored' => 0],
    ];

    /**
     * @var array<string, array{hunger_cost: int, fatigue_cost: int}>
     */
    private array $costsByRiskLevel = [
        'low' => ['hunger_cost' => 2, 'fatigue_cost' => 2],
        'medium' => ['hunger_cost' => 3, 'fatigue_cost' => 3],
        'high' => ['hunger_cost' => 4, 'fatigue_cost' => 5],
        'extreme' => ['hunger_cost' => 5, 'fatigue_cost' => 6],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->firstOrCreateItem([
                'key' => 'borscht',
                'name' => 'Borscht',
                'description' => 'A pot of deep red beet soup, ladled hot in the Kyiv metro. Fills the belly and warms tired bones.',
                'rarity' => 'common',
            ]);

            $this->firstOrCreateItem([
                'key' => 'vodka_fish',
                'name' => 'Vodka Fish',
                'description' => 'Salt fish cured in a splash of contraband vodka. Takes the edge off hunger and the long day behind you.',
                'rarity' => 'uncommon',
            ]);

            foreach ($this->foodValuesByItemKey as $itemKey => $foodValues) {
                DB::table('items')->where('key', $itemKey)->update([...$foodValues, 'updated_at' => now()]);
            }

            foreach ($this->costsByRiskLevel as $riskLevel => $costs) {
                DB::table('city_actions')->where('risk_level', $riskLevel)->update([...$costs, 'updated_at' => now()]);
            }

            $this->seedKyivCookingActions();
        });
    }

    /**
     * The items may be held by players by now, so rolling back only removes the cooking actions.
     */
    public function down(): void
    {
        DB::table('city_actions')->whereIn('action_key', ['borscht_cooking', 'vodka_fish_curing'])->delete();
    }

    private function seedKyivCookingActions(): void
    {
        $kyivId = DB::table('cities')->where('city', 'Kyiv')->value('id');

        if ($kyivId === null) {
            return;
        }

        $actions = [
            [
                'action_key' => 'borscht_cooking',
                'label' => 'Borscht cooking',
                'description' => 'Simmer foraged beets and clean water into borscht for the metro shelters.',
                'min_level' => 1,
                'base_duration_seconds' => 15,
                'reward_profile' => ['xp' => 18, 'item_key' => 'borscht', 'quantity' => 2, 'loot_tier' => 'medium'],
                'required_items' => [['item_key' => 'foraged_produce', 'quantity' => 2], ['item_key' => 'clean_water', 'quantity' => 1]],
            ],
            [
                'action_key' => 'vodka_fish_curing',
                'label' => 'Vodka fish curing',
                'description' => 'Cure salt fish with black-market vodka the way the old station cooks did.',
                'min_level' => 3,
                'base_duration_seconds' => 15,
                'reward_profile' => ['xp' => 20, 'item_key' => 'vodka_fish', 'quantity' => 2, 'loot_tier' => 'medium-high'],
                'required_items' => [['item_key' => 'salt_fish', 'quantity' => 2]],
            ],
        ];

        foreach ($actions as $action) {
            if (DB::table('city_actions')->where('action_key', $action['action_key'])->exists()) {
                continue;
            }

            DB::table('city_actions')->insert([
                ...$action,
                'city_id' => $kyivId,
                'skill_key' => 'cooking',
                'risk_level' => 'low',
                ...$this->costsByRiskLevel['low'],
                'reward_profile' => json_encode($action['reward_profile']),
                'required_items' => json_encode($action['required_items']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
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
