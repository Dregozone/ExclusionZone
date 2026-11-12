<?php

namespace App;

/**
 * What wore a survivor's health down. Combat will join these.
 */
enum HarmCause: string
{
    case Starvation = 'starvation';
    case Exhaustion = 'exhaustion';
    case Combat = 'combat';
    case Storm = 'storm';
    case Radiation = 'radiation';
    case Accident = 'accident';
    case Trouble = 'trouble';

    /**
     * How the survivor went down, told to them when they come round.
     */
    public function collapseDescription(): string
    {
        return match ($this) {
            self::Starvation => 'Hunger finally took your legs out from under you',
            self::Exhaustion => 'Your body gave out after pushing on long past exhaustion',
            self::Combat => 'You fell in the fight, bleeding into the dirt',
            self::Storm => 'The storm threw you down and kept you there',
            self::Radiation => 'The radiation burned through you until you could not stand',
            self::Accident => 'One wrong step on dangerous work left you bleeding in the dirt',
            self::Trouble => 'The fight at the edge of town went badly, and you went down hard',
        };
    }

    /**
     * How the survivor was hurt without going down, added to the report of the work or travel that did it.
     */
    public function injuryDescription(): string
    {
        return match ($this) {
            self::Starvation => 'Hunger gnawed at you',
            self::Exhaustion => 'Exhaustion wore you down',
            self::Combat => 'You were wounded in the fight',
            self::Storm => 'The storm battered you',
            self::Radiation => 'Radiation seeped into you',
            self::Accident => 'You were hurt on the job',
            self::Trouble => 'You were hurt in a fight',
        };
    }
}
