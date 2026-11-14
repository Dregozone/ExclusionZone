<?php

namespace App\Models;

use App\EquipSlot;
use App\ItemRarity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use HasFactory, SoftDeletes;

    protected $attributes = [
        'rarity' => 'common',
        'is_quest_item' => false,
        'hunger_restored' => 0,
        'fatigue_restored' => 0,
        'health_restored' => 0,
        'attack_bonus' => 0,
        'defence_bonus' => 0,
        'is_medical' => false,
        'work_speed_bonus_percent' => 0,
        'hunger_reduction_percent' => 0,
        'fatigue_reduction_percent' => 0,
        'healing_bonus_percent' => 0,
        'radiation_protection_percent' => 0,
        'base_price' => 0,
    ];

    /**
     * Item images found on disk, keyed by item key, so each file is only looked for once per request.
     *
     * @var array<string, bool>
     */
    private static array $imageExists = [];

    protected $fillable = [
        'key',
        'name',
        'description',
        'rarity',
        'is_quest_item',
        'hunger_restored',
        'fatigue_restored',
        'health_restored',
        'attack_bonus',
        'defence_bonus',
        'is_medical',
        'equip_slot',
        'bonus_skill_key',
        'work_speed_bonus_percent',
        'hunger_reduction_percent',
        'fatigue_reduction_percent',
        'healing_bonus_percent',
        'radiation_protection_percent',
        'base_price',
    ];

    protected function casts(): array
    {
        return [
            'rarity' => ItemRarity::class,
            'is_quest_item' => 'boolean',
            'hunger_restored' => 'integer',
            'fatigue_restored' => 'integer',
            'health_restored' => 'integer',
            'attack_bonus' => 'integer',
            'defence_bonus' => 'integer',
            'is_medical' => 'boolean',
            'work_speed_bonus_percent' => 'integer',
            'hunger_reduction_percent' => 'integer',
            'fatigue_reduction_percent' => 'integer',
            'healing_bonus_percent' => 'integer',
            'radiation_protection_percent' => 'integer',
            'base_price' => 'integer',
        ];
    }

    /**
     * The skill whose work this item speeds up when worn.
     */
    public function bonusSkill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'bonus_skill_key', 'key');
    }

    /**
     * What the item is worth when handed over to buy a way past trouble. Rarer items are worth more.
     */
    public function barterValue(): int
    {
        return $this->rarity->rank();
    }

    /**
     * Food is anything that eases hunger or fatigue, or restores health, when eaten. Medical items are used instead.
     */
    public function isFood(): bool
    {
        return ! $this->is_medical && ($this->hunger_restored > 0 || $this->fatigue_restored > 0 || $this->health_restored > 0);
    }

    /**
     * Medical items, such as bandages and medkits, are used from the pack to restore health.
     */
    public function isMedical(): bool
    {
        return $this->is_medical && $this->health_restored > 0;
    }

    /**
     * A weapon adds its attack bonus to every blow in a fight.
     */
    public function isWeapon(): bool
    {
        return $this->attack_bonus > 0;
    }

    /**
     * Armour lowers every enemy's chance to land a blow by its defence bonus.
     */
    public function isArmour(): bool
    {
        return $this->defence_bonus > 0;
    }

    /**
     * Wearable items fit one of the survivor's equipment slots.
     */
    public function isWearable(): bool
    {
        return EquipSlot::fitting($this) !== [];
    }

    /**
     * Bazaars buy anything with a price that is not a quest item.
     */
    public function isSellable(): bool
    {
        return ! $this->is_quest_item && $this->base_price > 0;
    }

    /**
     * What wearing the item does, in short phrases for the pack, gear screen and manual.
     *
     * @return list<string>
     */
    public function equipEffects(): array
    {
        if (! $this->isWearable()) {
            return [];
        }

        return array_values(array_filter([
            $this->work_speed_bonus_percent > 0 && $this->bonus_skill_key !== null
                ? ($this->bonusSkill?->display_name ?? str($this->bonus_skill_key)->headline()).' work '.$this->work_speed_bonus_percent.'% faster'
                : null,
            $this->attack_bonus > 0 ? 'Attack +'.$this->attack_bonus.' in fights' : null,
            $this->defence_bonus > 0 ? 'Defence +'.$this->defence_bonus.' in fights' : null,
            $this->hunger_reduction_percent > 0 ? 'Hunger -'.$this->hunger_reduction_percent.'% while working' : null,
            $this->fatigue_reduction_percent > 0 ? 'Fatigue -'.$this->fatigue_reduction_percent.'% while working' : null,
            $this->healing_bonus_percent > 0 ? 'Healing +'.$this->healing_bonus_percent.'%' : null,
            $this->radiation_protection_percent > 0 ? 'Radiation -'.$this->radiation_protection_percent.'%' : null,
        ]));
    }

    /**
     * The item's picture: a generated icon named after its key in public/images/items, or a plain crate for items added since.
     */
    public function imageUrl(): string
    {
        $key = (string) $this->key;

        self::$imageExists[$key] ??= $key !== '' && is_file(public_path("images/items/{$key}.jpg"));

        return asset(self::$imageExists[$key] ? "images/items/{$key}.jpg" : 'images/items/default.jpg');
    }
}
