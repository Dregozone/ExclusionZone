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
    | Blows can miss. The survivor lands a blow with the base hit chance, plus
    | the hit chance per skill level, minus the enemy's defence. The enemy lands
    | one with its accuracy, minus the evasion per skill level and the defence
    | bonus of the best armour the survivor carries. Both chances stay between
    | the minimum and maximum hit chance, so no fight is ever certain.
    |
    | A landed blow from the survivor hits for a roll between the base damage
    | values, plus the damage per skill level and the attack bonus of the best
    | weapon they carry. Hurt survivors hit softer: at 0 health a blow lands at
    | half strength, rising to full strength at full health.
    |
    | Every combat skill level also turns aside some of each landed enemy blow,
    | up to the maximum below. The enemy's own damage, accuracy, defence,
    | health, XP and loot are edited on the admin enemies page.
    |
    */

    'survivor' => [
        'base_damage_min' => (int) env('COMBAT_SURVIVOR_BASE_DAMAGE_MIN', 3),
        'base_damage_max' => (int) env('COMBAT_SURVIVOR_BASE_DAMAGE_MAX', 6),
        'damage_per_skill_level' => (int) env('COMBAT_DAMAGE_PER_SKILL_LEVEL', 1),
        'base_hit_chance_percent' => (int) env('COMBAT_SURVIVOR_BASE_HIT_CHANCE_PERCENT', 75),
        'hit_chance_percent_per_skill_level' => (int) env('COMBAT_HIT_CHANCE_PERCENT_PER_SKILL_LEVEL', 2),
        'evasion_percent_per_skill_level' => (int) env('COMBAT_EVASION_PERCENT_PER_SKILL_LEVEL', 1),
        'blocked_percent_per_skill_level' => (int) env('COMBAT_BLOCKED_PERCENT_PER_SKILL_LEVEL', 3),
        'maximum_blocked_percent' => (int) env('COMBAT_MAXIMUM_BLOCKED_PERCENT', 60),
    ],

    'hit_chance' => [
        'minimum_percent' => (int) env('COMBAT_MINIMUM_HIT_CHANCE_PERCENT', 5),
        'maximum_percent' => (int) env('COMBAT_MAXIMUM_HIT_CHANCE_PERCENT', 95),
    ],

    /*
    | A survivor whose health falls to this value or below after a round
    | breaks off and runs, ending the work loop with no reward for that fight.
    | They can't start a fight while this hurt either.
    */
    'flee_at_health' => (int) env('COMBAT_FLEE_AT_HEALTH', 20),

];
