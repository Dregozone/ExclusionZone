<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gives every existing item a rarity from the best loot tier of the actions that drop it,
 * and adds the Scorched items that only the Brain Scorcher in Pripyat can hand out.
 */
return new class extends Migration
{
    /**
     * @var array<string, string>
     */
    private array $rarityByLootTier = [
        'medium' => 'common',
        'medium-high' => 'uncommon',
        'high' => 'rare',
        'rare' => 'epic',
    ];

    /**
     * @var array<int, array{key: string, name: string, description: string}>
     */
    private array $scorchedItems = [
        [
            'key' => 'scorched_neural_lattice',
            'name' => 'Scorched Neural Lattice',
            'description' => 'A web of fused filaments still humming with someone else\'s memories. Pulled out of the Brain Scorcher still warm.',
        ],
        [
            'key' => 'psi_etched_relic',
            'name' => 'Psi-Etched Relic',
            'description' => 'A fist-sized shard covered in patterns nobody remembers carving. It hums louder near the reactor.',
        ],
        [
            'key' => 'emitter_heart',
            'name' => 'Emitter Heart',
            'description' => 'The glowing core of a Soviet psi-emitter. Scavengers whisper that it was never switched off.',
        ],
        [
            'key' => 'whispering_dosimeter',
            'name' => 'Whispering Dosimeter',
            'description' => 'It ticks in a rhythm that sounds like words. It always seems to know where the next hotspot is.',
        ],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->assignRaritiesFromLootTiers();

            foreach ($this->scorchedItems as $item) {
                DB::table('items')->updateOrInsert(
                    ['key' => $item['key']],
                    [...$item, 'rarity' => 'scorched', 'created_at' => now(), 'updated_at' => now()],
                );
            }
        });
    }

    public function down(): void
    {
        DB::table('items')->whereIn('key', array_column($this->scorchedItems, 'key'))->delete();
        DB::table('items')->update(['rarity' => 'common']);
    }

    private function assignRaritiesFromLootTiers(): void
    {
        $rarityRanks = array_flip(array_values($this->rarityByLootTier));
        $rarityByItemKey = [];

        foreach (DB::table('city_actions')->pluck('reward_profile') as $rewardProfile) {
            $reward = json_decode($rewardProfile, true) ?? [];
            $itemKey = $reward['item_key'] ?? null;
            $rarity = $this->rarityByLootTier[$reward['loot_tier'] ?? ''] ?? null;

            if ($itemKey === null || $rarity === null) {
                continue;
            }

            $currentRarity = $rarityByItemKey[$itemKey] ?? null;

            if ($currentRarity === null || $rarityRanks[$rarity] > $rarityRanks[$currentRarity]) {
                $rarityByItemKey[$itemKey] = $rarity;
            }
        }

        foreach ($rarityByItemKey as $itemKey => $rarity) {
            DB::table('items')->where('key', $itemKey)->update(['rarity' => $rarity, 'updated_at' => now()]);
        }
    }
};
