<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hunger and Fatigue
    |--------------------------------------------------------------------------
    |
    | Survivors grow hungry and tired as they work and travel. Both needs run
    | from 0 to 100 in steps of 0.1, and reaching a threshold below slows every work timer by
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
        'hunger_per_leg' => (float) env('SURVIVAL_TRAVEL_HUNGER_PER_LEG', 0.8),
        'fatigue_per_leg' => (float) env('SURVIVAL_TRAVEL_FATIGUE_PER_LEG', 0.9),
    ],

    'brain_scorcher' => [
        'hunger' => (float) env('SURVIVAL_BRAIN_SCORCHER_HUNGER', 0.5),
        'fatigue' => (float) env('SURVIVAL_BRAIN_SCORCHER_FATIGUE', 0.8),
    ],

    /*
    | Fatigue falls on its own while the survivor has no active work. Health
    | slowly comes back too, unless the survivor is starving. Set it to 0 to
    | heal only with food, medical items and clinics.
    */
    'rest' => [
        'fatigue_recovered_per_minute' => (float) env('SURVIVAL_FATIGUE_RECOVERED_PER_MINUTE', 1),
        'health_recovered_per_minute' => (int) env('SURVIVAL_HEALTH_RECOVERED_PER_MINUTE', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health
    |--------------------------------------------------------------------------
    |
    | Health runs from 0 to the maximum above and starts full. Every finished
    | work cycle, travel leg or Brain Scorcher burn taken while starving or
    | spent (hunger or fatigue at the maximum) costs health. Food with a
    | health value builds it back.
    |
    | At 0 the survivor dies: the SurvivorDied event fires, and someone
    | drags them to the nearest trade post on their continent. Their work
    | stops, scavengers take a share of every stack in their pack (quest
    | items are spared), and they wake up with the revive health below,
    | unable to work or travel until they have recovered.
    |
    */
    'health' => [
        'lost_while_starving' => (int) env('SURVIVAL_HEALTH_LOST_WHILE_STARVING', 5),
        'lost_while_spent' => (int) env('SURVIVAL_HEALTH_LOST_WHILE_SPENT', 5),
        'revive_health' => (int) env('SURVIVAL_REVIVE_HEALTH', 25),
    ],

    'death' => [
        'pack_lost_percent' => (int) env('SURVIVAL_DEATH_PACK_LOST_PERCENT', 25),
        'recovery_minutes' => (int) env('SURVIVAL_DEATH_RECOVERY_MINUTES', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | The Environment
    |--------------------------------------------------------------------------
    |
    | Every work cycle or travel leg finished in a storm has a chance to cost
    | health. Irradiated cities (radiation damage, edited on the locations
    | page) cost health for every cycle worked there and every leg that
    | arrives there, and each city action has its own chance of an accident
    | (edited on the city actions page).
    |
    */
    'environment' => [
        'storm_harm_chance_pct' => (int) env('SURVIVAL_STORM_HARM_CHANCE_PCT', 20),
        'storm_damage' => (int) env('SURVIVAL_STORM_DAMAGE', 5),
    ],

    /*
    | Thresholds in ascending order. The highest one reached applies.
    | Health runs the other way: its thresholds apply at or below their value,
    | and the lowest one reached applies.
    */
    'thresholds' => [
        'health' => [
            ['at' => 10, 'label' => 'Critical', 'timer_increase_percent' => 50],
            ['at' => 25, 'label' => 'Badly hurt', 'timer_increase_percent' => 25],
            ['at' => 50, 'label' => 'Wounded', 'timer_increase_percent' => 10],
        ],
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
