<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trouble holding a survivor at a city's edge. Nothing else can be done there until they
 * fight it, buy their way in, or run back to the city they came from.
 */
class TroubleEncounter extends Model
{
    protected $fillable = [
        'user_id',
        'city_id',
        'trouble_type_id',
        'origin_city_id',
        'queued_city_ids',
    ];

    protected function casts(): array
    {
        return [
            'queued_city_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class)->withTrashed();
    }

    public function troubleType(): BelongsTo
    {
        return $this->belongsTo(TroubleType::class)->withTrashed();
    }

    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id')->withTrashed();
    }

    /**
     * The stops the survivor's route still had after this city, in order.
     *
     * @return list<int>
     */
    public function queuedCityIds(): array
    {
        return array_values(array_map('intval', $this->queued_city_ids ?? []));
    }
}
