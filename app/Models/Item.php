<?php

namespace App\Models;

use App\ItemRarity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        'is_medical' => false,
    ];

    protected $fillable = [
        'key',
        'name',
        'description',
        'rarity',
        'is_quest_item',
        'hunger_restored',
        'fatigue_restored',
        'health_restored',
        'is_medical',
    ];

    protected function casts(): array
    {
        return [
            'rarity' => ItemRarity::class,
            'is_quest_item' => 'boolean',
            'hunger_restored' => 'integer',
            'fatigue_restored' => 'integer',
            'health_restored' => 'integer',
            'is_medical' => 'boolean',
        ];
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
}
