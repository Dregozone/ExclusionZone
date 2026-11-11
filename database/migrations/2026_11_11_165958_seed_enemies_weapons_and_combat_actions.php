<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds weapons, a handful of enemies in the cities with combat actions, and two beginner fights
 * in Kyiv so new survivors can learn to fight before they travel.
 */
return new class extends Migration
{
    /**
     * @var list<array{key: string, name: string, description: string, rarity: string, attack_bonus: int}>
     */
    private array $weapons = [
        ['key' => 'rusty_pipe', 'name' => 'Rusty Pipe', 'description' => 'A length of pipe wrapped in tape at one end. Crude, but it hits harder than a fist.', 'rarity' => 'common', 'attack_bonus' => 2],
        ['key' => 'combat_knife', 'name' => 'Combat Knife', 'description' => 'A smuggler\'s blade, kept sharp on harbour stone.', 'rarity' => 'uncommon', 'attack_bonus' => 4],
        ['key' => 'hunting_rifle', 'name' => 'Hunting Rifle', 'description' => 'A bolt-action rifle with a cracked stock and a true barrel.', 'rarity' => 'rare', 'attack_bonus' => 7],
    ];

    /**
     * @var list<array{city: string, key: string, name: string, description: string, skill_key: string, health: int, min_damage: int, max_damage: int, xp_reward: int, loot: list<array{item_key: string, chance_percent: int, quantity: int}>}>
     */
    private array $enemies = [
        [
            'city' => 'Kyiv', 'key' => 'feral_dog', 'name' => 'Feral Dog', 'skill_key' => 'combat_ranged',
            'description' => 'Ribs showing, teeth bared. The packs roam the empty boulevards at dusk.',
            'health' => 15, 'min_damage' => 2, 'max_damage' => 4, 'xp_reward' => 10,
            'loot' => [['item_key' => 'mutant_hide', 'chance_percent' => 30, 'quantity' => 1]],
        ],
        [
            'city' => 'Kyiv', 'key' => 'looter', 'name' => 'Looter', 'skill_key' => 'combat_melee',
            'description' => 'A desperate scavenger who would rather take your finds than look for their own.',
            'health' => 25, 'min_damage' => 3, 'max_damage' => 6, 'xp_reward' => 15,
            'loot' => [
                ['item_key' => 'scrap_metal', 'chance_percent' => 60, 'quantity' => 1],
                ['item_key' => 'ration_tokens', 'chance_percent' => 30, 'quantity' => 1],
                ['item_key' => 'rusty_pipe', 'chance_percent' => 25, 'quantity' => 1],
            ],
        ],
        [
            'city' => 'Pripyat', 'key' => 'mutated_boar', 'name' => 'Mutated Boar', 'skill_key' => 'combat_ranged',
            'description' => 'Twice the size it should be, with bristles that glow faintly in the dark. Do not let it close the distance.',
            'health' => 60, 'min_damage' => 6, 'max_damage' => 12, 'xp_reward' => 40,
            'loot' => [
                ['item_key' => 'mutant_hide', 'chance_percent' => 80, 'quantity' => 2],
                ['item_key' => 'irradiated_samples', 'chance_percent' => 20, 'quantity' => 1],
            ],
        ],
        [
            'city' => 'Gdansk', 'key' => 'harbour_smuggler', 'name' => 'Harbour Smuggler', 'skill_key' => 'combat_melee',
            'description' => 'A dockside enforcer who settles arguments with a blade.',
            'health' => 35, 'min_damage' => 4, 'max_damage' => 8, 'xp_reward' => 25,
            'loot' => [
                ['item_key' => 'contraband_cache', 'chance_percent' => 35, 'quantity' => 1],
                ['item_key' => 'combat_knife', 'chance_percent' => 15, 'quantity' => 1],
            ],
        ],
        [
            'city' => 'Detroit', 'key' => 'armed_bandit', 'name' => 'Armed Bandit', 'skill_key' => 'combat_ranged',
            'description' => 'A foundry gang gunman, well fed and better armed than most.',
            'health' => 50, 'min_damage' => 6, 'max_damage' => 10, 'xp_reward' => 35,
            'loot' => [
                ['item_key' => 'ammo_crates', 'chance_percent' => 40, 'quantity' => 1],
                ['item_key' => 'hunting_rifle', 'chance_percent' => 10, 'quantity' => 1],
            ],
        ],
    ];

    /**
     * @var list<array{action_key: string, label: string, description: string, skill_key: string, reward_profile: array{xp: int, item_key: string, quantity: int, loot_tier: string}}>
     */
    private array $kyivCombatActions = [
        [
            'action_key' => 'clear_the_ruins',
            'label' => 'Clear the ruins',
            'description' => 'Drive looters out of the collapsed blocks around the metro entrances.',
            'skill_key' => 'combat_melee',
            'reward_profile' => ['xp' => 15, 'item_key' => 'scrap_metal', 'quantity' => 1, 'loot_tier' => 'medium'],
        ],
        [
            'action_key' => 'hunt_dog_packs',
            'label' => 'Hunt the dog packs',
            'description' => 'Pick off the feral dogs stalking the boulevards before they find the shelters.',
            'skill_key' => 'combat_ranged',
            'reward_profile' => ['xp' => 15, 'item_key' => 'mutant_hide', 'quantity' => 1, 'loot_tier' => 'medium'],
        ],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->weapons as $weapon) {
                if (DB::table('items')->where('key', $weapon['key'])->exists()) {
                    continue;
                }

                DB::table('items')->insert([...$weapon, 'created_at' => now(), 'updated_at' => now()]);
            }

            $cityIdsByName = DB::table('cities')->pluck('id', 'city');

            foreach ($this->enemies as $enemy) {
                $cityId = $cityIdsByName[$enemy['city']] ?? null;

                if ($cityId === null || DB::table('enemies')->where('key', $enemy['key'])->exists()) {
                    continue;
                }

                $attributes = array_diff_key($enemy, ['city' => true]);

                DB::table('enemies')->insert([
                    ...$attributes,
                    'city_id' => $cityId,
                    'loot' => json_encode($enemy['loot']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->seedKyivCombatActions($cityIdsByName['Kyiv'] ?? null);
        });
    }

    /**
     * Weapons may be held by players by now, so rolling back leaves the items in place.
     */
    public function down(): void
    {
        DB::table('city_actions')->whereIn('action_key', array_column($this->kyivCombatActions, 'action_key'))->delete();
        DB::table('enemies')->whereIn('key', array_column($this->enemies, 'key'))->delete();
    }

    private function seedKyivCombatActions(?int $kyivId): void
    {
        if ($kyivId === null) {
            return;
        }

        foreach ($this->kyivCombatActions as $action) {
            if (DB::table('city_actions')->where('action_key', $action['action_key'])->exists()) {
                continue;
            }

            DB::table('city_actions')->insert([
                ...$action,
                'city_id' => $kyivId,
                'min_level' => 1,
                'risk_level' => 'medium',
                'base_duration_seconds' => 20,
                'hunger_cost' => 3,
                'fatigue_cost' => 3,
                'reward_profile' => json_encode($action['reward_profile']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
