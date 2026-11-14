<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives the seeded enemies their accuracy and defence, tougher enemies landing more blows and dodging
 * more of the survivor's, and adds two pieces of armour that a few of them drop.
 */
return new class extends Migration
{
    /**
     * @var array<string, array{accuracy: int, defence: int}>
     */
    private array $enemyStats = [
        'feral_dog' => ['accuracy' => 50, 'defence' => 10],
        'looter' => ['accuracy' => 55, 'defence' => 5],
        'harbour_smuggler' => ['accuracy' => 65, 'defence' => 15],
        'armed_bandit' => ['accuracy' => 70, 'defence' => 15],
        'mutated_boar' => ['accuracy' => 70, 'defence' => 20],
    ];

    /**
     * @var list<array{key: string, name: string, description: string, rarity: string, defence_bonus: int}>
     */
    private array $armour = [
        ['key' => 'padded_jacket', 'name' => 'Padded Jacket', 'description' => 'A quilted work jacket stuffed with newspaper and cut-up tyres. It takes the edge off a blow.', 'rarity' => 'uncommon', 'defence_bonus' => 8],
        ['key' => 'riot_vest', 'name' => 'Riot Vest', 'description' => 'Police surplus with the insignia scratched off. Heavy, hot, and worth it.', 'rarity' => 'rare', 'defence_bonus' => 15],
    ];

    /**
     * @var array<string, array{item_key: string, chance_percent: int, quantity: int}>
     */
    private array $armourDrops = [
        'looter' => ['item_key' => 'padded_jacket', 'chance_percent' => 8, 'quantity' => 1],
        'harbour_smuggler' => ['item_key' => 'padded_jacket', 'chance_percent' => 15, 'quantity' => 1],
        'armed_bandit' => ['item_key' => 'riot_vest', 'chance_percent' => 8, 'quantity' => 1],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->enemyStats as $key => $stats) {
                DB::table('enemies')->where('key', $key)->update($stats);
            }

            foreach ($this->armour as $armour) {
                if (DB::table('items')->where('key', $armour['key'])->exists()) {
                    continue;
                }

                DB::table('items')->insert([...$armour, 'created_at' => now(), 'updated_at' => now()]);
            }

            foreach ($this->armourDrops as $enemyKey => $drop) {
                $enemy = DB::table('enemies')->where('key', $enemyKey)->first(['id', 'loot']);

                if ($enemy === null) {
                    continue;
                }

                $loot = json_decode($enemy->loot ?? '[]', true) ?: [];

                if (collect($loot)->contains('item_key', $drop['item_key'])) {
                    continue;
                }

                DB::table('enemies')->where('id', $enemy->id)->update(['loot' => json_encode([...$loot, $drop])]);
            }
        });
    }

    /**
     * Armour may be held by players by now, so rolling back leaves the items in place and only stops enemies dropping it.
     */
    public function down(): void
    {
        foreach (array_keys($this->armourDrops) as $enemyKey) {
            $enemy = DB::table('enemies')->where('key', $enemyKey)->first(['id', 'loot']);

            if ($enemy === null) {
                continue;
            }

            $loot = collect(json_decode($enemy->loot ?? '[]', true) ?: [])
                ->reject(fn (array $entry): bool => in_array($entry['item_key'], array_column($this->armour, 'key'), true))
                ->values()
                ->all();

            DB::table('enemies')->where('id', $enemy->id)->update(['loot' => json_encode($loot)]);
        }
    }
};
