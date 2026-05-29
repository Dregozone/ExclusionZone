<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Country extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'continent',
        'country',
        'avg_temp_c',
        'rain_chance_pct',
        'trouble_chance_pct',
        'notes',
    ];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
