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
        'reward_profile',
    ];

    protected function casts(): array
    {
        return [
            'reward_profile' => 'array',
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
