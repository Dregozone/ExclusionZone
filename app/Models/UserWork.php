<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWork extends Model
{
    protected $fillable = [
        'user_id',
        'work_type',
        'city_action_id',
        'origin_city_id',
        'destination_city_id',
        'skill_key',
        'duration_seconds',
        'available_at',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'immutable_datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cityAction(): BelongsTo
    {
        return $this->belongsTo(CityAction::class)->withTrashed();
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id')->withTrashed();
    }

    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id')->withTrashed();
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_key', 'key');
    }

    public function isCityAction(): bool
    {
        return $this->work_type === 'city_action';
    }

    public function isTravel(): bool
    {
        return $this->work_type === 'travel';
    }
}
