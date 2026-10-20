<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cooking and crafting actions now turn raw materials into finished goods, so each
 * one needs its inputs in the player's inventory before the work can begin.
 */
return new class extends Migration
{
    /**
     * @var array<string, array<string, int>>
     */
    private array $requiredItemsByActionKey = [
        'ration_cooking' => ['foraged_produce' => 2],
        'fish_processing' => ['fresh_fish' => 2],
        'ammo_crafting' => ['scrap_metal' => 2],
        'armor_crafting' => ['scrap_metal' => 2],
        'weapon_assembly' => ['metal_parts' => 2],
        'herbal_medicine_crafting' => ['rare_herbs' => 1],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->ensureFreshFishExists();

            foreach ($this->requiredItemsByActionKey as $actionKey => $quantitiesByItemKey) {
                $existingItemCount = DB::table('items')->whereIn('key', array_keys($quantitiesByItemKey))->count();

                if ($existingItemCount !== count($quantitiesByItemKey)) {
                    continue;
                }

                $requiredItems = [];

                foreach ($quantitiesByItemKey as $itemKey => $quantity) {
                    $requiredItems[] = ['item_key' => $itemKey, 'quantity' => $quantity];
                }

                DB::table('city_actions')
                    ->where('action_key', $actionKey)
                    ->update(['required_items' => json_encode($requiredItems), 'updated_at' => now()]);
            }
        });
    }

    public function down(): void
    {
        DB::table('city_actions')
            ->whereIn('action_key', array_keys($this->requiredItemsByActionKey))
            ->update(['required_items' => null, 'updated_at' => now()]);
    }

    /**
     * Canal and fjord fishing already reward fresh fish, but the item was never seeded.
     */
    private function ensureFreshFishExists(): void
    {
        if (DB::table('items')->where('key', 'fresh_fish')->exists()) {
            return;
        }

        DB::table('items')->insert([
            'key' => 'fresh_fish',
            'name' => 'Fresh Fish',
            'description' => 'A raw catch from the canals and fjords. Spoils fast unless it is processed.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
