<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fights
    |--------------------------------------------------------------------------
    |
    | A city action using a combat skill in a city with an enemy testing that
    | skill is a fight. Each cycle of its work timer is one round: the survivor
    | strikes first, then the enemy strikes back if it is still standing.
    |
    | The survivor hits for a roll between the base damage values, plus the
    | damage per skill level and the attack bonus of the best weapon they
    | carry. Hurt survivors hit softer: at 0 health a blow lands at half
    | strength, rising to full strength at full health.
    |
    | Every combat skill level turns aside some of each enemy blow, up to the
    | maximum below. The enemy's own damage, health, XP and loot are edited on
    | the admin enemies page.
    |
    */

    'survivor' => [
        'base_damage_min' => (int) env('COMBAT_SURVIVOR_BASE_DAMAGE_MIN', 3),
        'base_damage_max' => (int) env('COMBAT_SURVIVOR_BASE_DAMAGE_MAX', 6),
        'damage_per_skill_level' => (int) env('COMBAT_DAMAGE_PER_SKILL_LEVEL', 1),
        'blocked_percent_per_skill_level' => (int) env('COMBAT_BLOCKED_PERCENT_PER_SKILL_LEVEL', 3),
        'maximum_blocked_percent' => (int) env('COMBAT_MAXIMUM_BLOCKED_PERCENT', 60),
    ],

    /*
    | A survivor whose health falls to this value or below after a round
    | breaks off and runs, ending the work loop with no reward for that fight.
    | They can't start a fight while this hurt either.
    */
    'flee_at_health' => (int) env('COMBAT_FLEE_AT_HEALTH', 20),

];
