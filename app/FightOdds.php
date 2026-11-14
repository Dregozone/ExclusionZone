<?php

namespace App;

/**
 * How a fight against an enemy is likely to go, judged by how much of the survivor's health above the
 * flee threshold the fight is expected to cost.
 */
enum FightOdds: string
{
    case Easy = 'easy';
    case Fair = 'fair';
    case Risky = 'risky';
    case Deadly = 'deadly';

    /**
     * The odds for a fight expected to cost this share of the health the survivor can lose before fleeing.
     */
    public static function forHealthCostPercent(float $percent): self
    {
        return match (true) {
            $percent <= 33 => self::Easy,
            $percent <= 66 => self::Fair,
            $percent <= 100 => self::Risky,
            default => self::Deadly,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Easy',
            self::Fair => 'Fair fight',
            self::Risky => 'Risky',
            self::Deadly => 'Likely to flee',
        };
    }

    public function advice(): string
    {
        return match ($this) {
            self::Easy => 'You should win with health to spare.',
            self::Fair => 'You should win, but it will cost you.',
            self::Risky => 'You might win, only just. Bring food and medical items.',
            self::Deadly => 'You are likely to be driven off before it falls. Train or find better gear first.',
        };
    }
}
