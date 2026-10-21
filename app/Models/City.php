<?php

namespace App\Models;

use App\Weather;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'weather' => 'clear',
    ];

    protected $fillable = [
        'country_id',
        'city',
        'biome',
        'rain_chance_pct',
        'trouble_chance_pct',
        'weather',
        'baseline_loot_tier',
        'has_trade_post',
        'lat',
        'lng',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'weather' => Weather::class,
        'has_trade_post' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(CityAction::class);
    }

    public function neighbors(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'city_connections', 'city_id', 'neighbor_city_id')
            ->withPivot('duration_seconds');
    }
}
