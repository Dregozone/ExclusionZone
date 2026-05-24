<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillLevelRule extends Model
{
    protected $fillable = [
        'tier',
        'level_min',
        'level_max',
        'unlock_examples',
    ];
}
