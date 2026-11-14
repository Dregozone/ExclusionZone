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

        while ($this->xp >= $this->xpForNextLevel()) {
            $this->xp -= $this->xpForNextLevel();
            $this->level++;
            $levelsGained++;
        }

        $this->save();

        return $levelsGained;
    }

    /**
     * Total XP needed within the current level to reach the next one.
     */
    public function xpForNextLevel(): int
    {
        return $this->level * 100;
    }

    /**
     * XP still to earn before the next level.
     */
    public function xpToNextLevel(): int
    {
        return max(0, $this->xpForNextLevel() - $this->xp);
    }

    /**
     * How far through the current level the skill is, from 0 to 100.
     */
    public function progressPercent(): int
    {
        return (int) min(100, floor($this->xp / max(1, $this->xpForNextLevel()) * 100));
    }
}
