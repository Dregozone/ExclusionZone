<?php

namespace App\Models;

use App\Weather;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWork extends Model
{
    protected $attributes = [
        'weather' => 'clear',
    ];

    protected $fillable = [
        'user_id',
        'work_type',
        'city_action_id',
        'origin_city_id',
        'destination_city_id',
        'offered_item_id',
        'offered_quantity',
        'skill_key',
        'duration_seconds',
        'weather',
        'available_at',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'immutable_datetime',
            'offered_quantity' => 'integer',
            'weather' => Weather::class,
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

    public function offeredItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'offered_item_id')->withTrashed();
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

    public function isBrainScorcher(): bool
    {
        return $this->work_type === 'brain_scorcher';
    }
}
