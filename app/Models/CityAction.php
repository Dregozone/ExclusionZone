<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CityAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'city_id',
        'action_key',
        'label',
        'description',
        'skill_key',
        'min_level',
        'risk_level',
        'hazard_chance_pct',
        'hazard_damage',
        'base_duration_seconds',
        'hunger_cost',
        'fatigue_cost',
        'reward_profile',
        'required_items',
    ];

    protected function casts(): array
    {
        return [
            'hazard_chance_pct' => 'integer',
            'hazard_damage' => 'integer',
            'base_duration_seconds' => 'integer',
            'hunger_cost' => 'integer',
            'fatigue_cost' => 'integer',
            'reward_profile' => 'array',
            'required_items' => 'array',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_key', 'key');
    }
}
