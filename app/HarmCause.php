<?php

namespace App;

/**
 * What wore a survivor's health down. Combat and the environment will join these.
 */
enum HarmCause: string
{
    case Starvation = 'starvation';
    case Exhaustion = 'exhaustion';
    case Trouble = 'trouble';

    /**
     * How the survivor went down, told to them when they come round.
     */
    public function collapseDescription(): string
    {
        return match ($this) {
            self::Starvation => 'Hunger finally took your legs out from under you',
            self::Exhaustion => 'Your body gave out after pushing on long past exhaustion',
            self::Trouble => 'The fight at the edge of town went badly, and you went down hard',
        };
    }
}
