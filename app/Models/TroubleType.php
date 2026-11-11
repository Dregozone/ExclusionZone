<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Who might be blocking the way into a city, how hard they fight, what beating them pays,
 * and how much in goods they want to let a survivor pass.
 */
class TroubleType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'skill_key',
        'fighter_level',
        'damage_min',
        'damage_max',
        'xp_reward',
        'loot_item_id',
        'loot_quantity',
        'demand_value',
    ];

    protected function casts(): array
    {
        return [
            'fighter_level' => 'integer',
            'damage_min' => 'integer',
            'damage_max' => 'integer',
            'xp_reward' => 'integer',
            'loot_quantity' => 'integer',
            'demand_value' => 'integer',
        ];
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_key', 'key');
    }

    public function lootItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'loot_item_id')->withTrashed();
    }
}
