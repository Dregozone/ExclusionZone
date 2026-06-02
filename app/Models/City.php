<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class City extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'country_id',
        'city',
        'biome',
        'rain_chance_pct',
        'trouble_chance_pct',
        'baseline_loot_tier',
        'lat',
        'lng',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
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
        return $this->belongsToMany(self::class, 'city_connections', 'city_id', 'neighbor_city_id');
    }
}
