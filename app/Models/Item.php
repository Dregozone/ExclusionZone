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
        'attack_bonus' => 0,
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
        'attack_bonus',
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
        ];
    }

    /**
     * Food is anything that eases hunger or fatigue, or restores health, when eaten.
     */
    public function isFood(): bool
    {
        return $this->hunger_restored > 0 || $this->fatigue_restored > 0 || $this->health_restored > 0;
    }

    /**
     * A weapon adds its attack bonus to every blow in a fight.
     */
    public function isWeapon(): bool
    {
        return $this->attack_bonus > 0;
    }
}
