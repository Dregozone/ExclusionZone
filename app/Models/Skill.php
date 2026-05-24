<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skill extends Model
{
    protected $fillable = [
        'key',
        'display_name',
        'description',
    ];

    public function actions(): HasMany
    {
        return $this->hasMany(CityAction::class, 'skill_key', 'key');
    }
}
