<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trouble on Arrival
    |--------------------------------------------------------------------------
    |
    | When a survivor arrives in a city, the city's trouble chance is rolled.
    | On their first visit the full chance applies; on later visits only the
    | percentage of it below. Trouble holds the survivor at the city's edge
    | until they attack, buy their way in, or run back where they came from.
    |
    | Who causes trouble, how hard they fight and what they demand are edited
    | on the Trouble admin page. The values here cover everything else.
    |
    */

    'enabled' => (bool) env('TROUBLE_ENABLED', true),

    'return_visit_chance_percent' => (int) env('TROUBLE_RETURN_VISIT_CHANCE_PERCENT', 25),

    /*
    | Attacking: the chance to win starts at the base, moves by the per-level
    | amount for each combat skill level the survivor has above or below the
    | trouble's fighter level, and by one point for every so many health
    | points the survivor has above or below half health. It always stays
    | between the minimum and maximum.
    |
    | Damage is rolled between the trouble's minimum and maximum. A win only
    | costs the percentage of it below; a loss costs all of it and the
    | trouble stays put.
    */
    'fight' => [
        'base_win_chance_percent' => (int) env('TROUBLE_BASE_WIN_CHANCE_PERCENT', 55),
        'win_chance_per_level_percent' => (int) env('TROUBLE_WIN_CHANCE_PER_LEVEL_PERCENT', 5),
        'health_points_per_win_chance_percent' => (int) env('TROUBLE_HEALTH_POINTS_PER_WIN_CHANCE_PERCENT', 5),
        'minimum_win_chance_percent' => 5,
        'maximum_win_chance_percent' => 95,
        'damage_taken_on_win_percent' => (int) env('TROUBLE_DAMAGE_TAKEN_ON_WIN_PERCENT', 50),
    ],

];
