<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumCosmetic extends Model
{
    protected $fillable = [
        'cosmetic_type',
        'name',
        'gameplay_bonus',
    ];
}
