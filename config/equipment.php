<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Worn Gear
    |--------------------------------------------------------------------------
    |
    | Survivors wear one item on the head, the body and the feet, and hold one
    | in each arm. Each item's bonuses are edited on the admin items page and
    | add up across everything worn, up to the caps below.
    |
    | work_speed: how much faster a skill's work cycles run.
    | hunger_reduction / fatigue_reduction: how much less hunger and fatigue
    |   every work cycle and travel leg adds.
    | healing_bonus: how much more health food, medical items, field clinics
    |   and resting give back.
    | radiation_protection: how much less health radiation takes.
    |
    | Only weapons held in an arm count in a fight, and the stronger of the
    | two is the one used.
    |
    */

    'caps' => [
        'work_speed' => (int) env('EQUIPMENT_MAXIMUM_WORK_SPEED_PERCENT', 50),
        'hunger_reduction' => (int) env('EQUIPMENT_MAXIMUM_HUNGER_REDUCTION_PERCENT', 75),
        'fatigue_reduction' => (int) env('EQUIPMENT_MAXIMUM_FATIGUE_REDUCTION_PERCENT', 75),
        'healing_bonus' => (int) env('EQUIPMENT_MAXIMUM_HEALING_BONUS_PERCENT', 100),
        'radiation_protection' => (int) env('EQUIPMENT_MAXIMUM_RADIATION_PROTECTION_PERCENT', 90),
    ],

];
