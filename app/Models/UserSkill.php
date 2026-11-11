<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{
    protected $fillable = [
        'user_id',
        'skill_id',
        'level',
        'xp',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }

    /**
     * Add experience and level up for every full level it covers.
     *
     * @return int How many levels were gained.
     */
    public function gainExperience(int $xp): int
    {
        $levelsGained = 0;
        $this->xp += $xp;

        while ($this->xp >= ($this->level * 100)) {
            $this->xp -= $this->level * 100;
            $this->level++;
            $levelsGained++;
        }

        $this->save();

        return $levelsGained;
    }
}
