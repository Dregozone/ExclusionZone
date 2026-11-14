<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Adds the first wearable gear (boots, masks, suits and hand tools), lets the existing weapons and
 * Scorched relics be worn, and prices every tradeable item by its rarity.
 */
return new class extends Migration
{
    /**
     * Base price in scrip for each rarity. Quest items are never priced, so bazaars will not take them.
     *
     * @var array<string, int>
     */
    private array $pricesByRarity = [
        'common' => 4,
        'uncommon' => 10,
        'rare' => 24,
        'epic' => 60,
        'scorched' => 150,
    ];

    /**
     * @var list<array<string, mixed>>
     */
    private array $wearables = [
        [
            'key' => 'wellington_boots', 'name' => 'Wellington Boots', 'rarity' => 'common', 'base_price' => 14,
            'description' => 'Knee-high rubber boots, patched twice. Keep your feet dry on the riverbank.',
            'equip_slot' => 'feet', 'bonus_skill_key' => 'fishing', 'work_speed_bonus_percent' => 15, 'fatigue_reduction_percent' => 5,
        ],
        [
            'key' => 'trail_boots', 'name' => 'Trail Boots', 'rarity' => 'uncommon', 'base_price' => 26,
            'description' => 'Broken-in leather boots with deep treads for long days in the wild.',
            'equip_slot' => 'feet', 'bonus_skill_key' => 'survival', 'work_speed_bonus_percent' => 10, 'fatigue_reduction_percent' => 15,
        ],
        [
            'key' => 'gas_mask', 'name' => 'Gas Mask', 'rarity' => 'uncommon', 'base_price' => 30,
            'description' => 'A Soviet GP-5 mask with a fresh filter. Breathe easier near the hotspots.',
            'equip_slot' => 'head', 'radiation_protection_percent' => 40,
        ],
        [
            'key' => 'hard_hat', 'name' => 'Hard Hat', 'rarity' => 'common', 'base_price' => 12,
            'description' => 'A dented site helmet with a working head lamp. Falling debris bounces off.',
            'equip_slot' => 'head', 'bonus_skill_key' => 'construction', 'work_speed_bonus_percent' => 15,
        ],
        [
            'key' => 'hazmat_suit', 'name' => 'Hazmat Suit', 'rarity' => 'rare', 'base_price' => 70,
            'description' => 'A sealed rubber suit from a reactor locker. Hot and heavy, but the dosimeter goes quiet.',
            'equip_slot' => 'body', 'radiation_protection_percent' => 60,
        ],
        [
            'key' => 'cooks_apron', 'name' => 'Cook\'s Apron', 'rarity' => 'common', 'base_price' => 10,
            'description' => 'A stained canvas apron with deep pockets for spices and a ladle.',
            'equip_slot' => 'body', 'bonus_skill_key' => 'cooking', 'work_speed_bonus_percent' => 15, 'hunger_reduction_percent' => 10,
        ],
        [
            'key' => 'medic_satchel', 'name' => 'Medic\'s Satchel', 'rarity' => 'uncommon', 'base_price' => 28,
            'description' => 'A shoulder bag of clamps, gauze and steady habits. Everything heals a little better.',
            'equip_slot' => 'body', 'bonus_skill_key' => 'medicine', 'work_speed_bonus_percent' => 15, 'healing_bonus_percent' => 20,
        ],
        [
            'key' => 'ghillie_cloak', 'name' => 'Ghillie Cloak', 'rarity' => 'uncommon', 'base_price' => 26,
            'description' => 'Burlap strips and dead leaves sewn onto netting. Lie still and you vanish.',
            'equip_slot' => 'body', 'bonus_skill_key' => 'stealth', 'work_speed_bonus_percent' => 20,
        ],
        [
            'key' => 'fishing_rod', 'name' => 'Fishing Rod', 'rarity' => 'common', 'base_price' => 12,
            'description' => 'A telescopic rod with a squeaky reel and a lucky lure.',
            'equip_slot' => 'arm', 'bonus_skill_key' => 'fishing', 'work_speed_bonus_percent' => 20,
        ],
        [
            'key' => 'claw_hammer', 'name' => 'Claw Hammer', 'rarity' => 'common', 'base_price' => 12,
            'description' => 'A heavy framing hammer. Drives nails, pulls them out, and settles arguments.',
            'equip_slot' => 'arm', 'bonus_skill_key' => 'construction', 'work_speed_bonus_percent' => 20, 'attack_bonus' => 3,
        ],
        [
            'key' => 'crowbar', 'name' => 'Crowbar', 'rarity' => 'common', 'base_price' => 12,
            'description' => 'A long steel pry bar. No locker stays shut for long.',
            'equip_slot' => 'arm', 'bonus_skill_key' => 'scavenging', 'work_speed_bonus_percent' => 15, 'attack_bonus' => 2,
        ],
        [
            'key' => 'soldering_iron', 'name' => 'Soldering Iron', 'rarity' => 'uncommon', 'base_price' => 24,
            'description' => 'A battery soldering iron with a steady tip. Circuit boards come back to life.',
            'equip_slot' => 'arm', 'bonus_skill_key' => 'engineering', 'work_speed_bonus_percent' => 20,
        ],
    ];

    /**
     * Gear that already exists in the Zone and can now be worn.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $existingGear = [
        'rusty_pipe' => ['equip_slot' => 'arm'],
        'padded_jacket' => ['equip_slot' => 'body', 'hunger_reduction_percent' => 5, 'fatigue_reduction_percent' => 10],
        'riot_vest' => ['equip_slot' => 'body'],
        'combat_knife' => ['equip_slot' => 'arm', 'bonus_skill_key' => 'hunting', 'work_speed_bonus_percent' => 10],
        'hunting_rifle' => ['equip_slot' => 'arm', 'bonus_skill_key' => 'hunting', 'work_speed_bonus_percent' => 15],
        'scorched_neural_lattice' => ['equip_slot' => 'head', 'bonus_skill_key' => 'engineering', 'work_speed_bonus_percent' => 30, 'fatigue_reduction_percent' => 15],
        'psi_etched_relic' => ['equip_slot' => 'arm', 'healing_bonus_percent' => 40, 'fatigue_reduction_percent' => 20],
        'emitter_heart' => ['equip_slot' => 'body', 'hunger_reduction_percent' => 25, 'fatigue_reduction_percent' => 25, 'radiation_protection_percent' => 30],
        'whispering_dosimeter' => ['equip_slot' => 'head', 'radiation_protection_percent' => 50, 'healing_bonus_percent' => 20],
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ($this->pricesByRarity as $rarity => $price) {
                DB::table('items')
                    ->where('rarity', $rarity)
                    ->where('is_quest_item', false)
                    ->where('base_price', 0)
                    ->update(['base_price' => $price]);
            }

            foreach ($this->wearables as $wearable) {
                if (DB::table('items')->where('key', $wearable['key'])->exists()) {
                    continue;
                }

                DB::table('items')->insert([...$wearable, 'created_at' => now(), 'updated_at' => now()]);
            }

            foreach ($this->existingGear as $key => $stats) {
                DB::table('items')->where('key', $key)->update($stats);
            }
        });
    }

    /**
     * Wearables may be in packs by now, so rolling back leaves the items and prices in place.
     */
    public function down(): void {}
};
