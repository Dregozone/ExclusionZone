<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hunger and Fatigue
    |--------------------------------------------------------------------------
    |
    | Survivors grow hungry and tired as they work and travel. Both needs run
    | from 0 to 100, and reaching a threshold below slows every work timer by
    | its percentage (hunger and fatigue add up). Work never stops because of
    | them; it only takes longer until the survivor eats or rests.
    |
    | Each city action sets its own hunger and fatigue cost per cycle, and each
    | food item sets how much it restores (both are edited on the admin pages).
    | The values here cover everything else.
    |
    */

    'maximum' => 100,

    'travel' => [
        'hunger_per_leg' => (int) env('SURVIVAL_TRAVEL_HUNGER_PER_LEG', 4),
        'fatigue_per_leg' => (int) env('SURVIVAL_TRAVEL_FATIGUE_PER_LEG', 5),
    ],

    'brain_scorcher' => [
        'hunger' => (int) env('SURVIVAL_BRAIN_SCORCHER_HUNGER', 1),
        'fatigue' => (int) env('SURVIVAL_BRAIN_SCORCHER_FATIGUE', 4),
    ],

    /*
    | Fatigue falls on its own while the survivor has no active work.
    */
    'rest' => [
        'fatigue_recovered_per_minute' => (int) env('SURVIVAL_FATIGUE_RECOVERED_PER_MINUTE', 1),
    ],

    /*
    | Thresholds in ascending order. The highest one reached applies.
    */
    'thresholds' => [
        'hunger' => [
            ['at' => 50, 'label' => 'Hungry', 'timer_increase_percent' => 10],
            ['at' => 75, 'label' => 'Famished', 'timer_increase_percent' => 25],
            ['at' => 100, 'label' => 'Starving', 'timer_increase_percent' => 50],
        ],
        'fatigue' => [
            ['at' => 50, 'label' => 'Tired', 'timer_increase_percent' => 10],
            ['at' => 75, 'label' => 'Exhausted', 'timer_increase_percent' => 25],
            ['at' => 100, 'label' => 'Spent', 'timer_increase_percent' => 50],
        ],
    ],

];
