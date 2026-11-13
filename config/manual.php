<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Survivor's Manual Guides
    |--------------------------------------------------------------------------
    |
    | The hand-written guide pages of the public manual, in reading order. Each
    | one has a view in resources/views/manual/guides named after its key. The
    | data pages (skills, locations, items, quests) are built from the live game
    | data and need no entry here.
    |
    */

    'guides' => [
        'getting-started' => [
            'title' => 'Just arrived',
            'summary' => 'Your first hour in the Zone, step by step.',
            'icon' => 'flag',
            'image' => 'images/work/travel.jpg',
        ],
        'city-menu' => [
            'title' => 'Reading the city menu',
            'summary' => 'Every part of the game screen, labelled.',
            'icon' => 'map-pin',
            'image' => 'images/work/default.jpg',
        ],
        'skills-and-work' => [
            'title' => 'Skills & work loops',
            'summary' => 'How actions, timers, XP and levels fit together.',
            'icon' => 'academic-cap',
            'image' => 'images/work/crafting.jpg',
        ],
        'survival' => [
            'title' => 'Staying alive',
            'summary' => 'Hunger, fatigue, health, weather, radiation and death.',
            'icon' => 'heart',
            'image' => 'images/work/survival.jpg',
        ],
        'travel' => [
            'title' => 'Travel & trouble',
            'summary' => 'Routes between cities and what waits at the edge of them.',
            'icon' => 'map',
            'image' => 'images/work/stealth.jpg',
        ],
        'combat' => [
            'title' => 'Fights',
            'summary' => 'Enemies, weapons, damage and when to run.',
            'icon' => 'fire',
            'image' => 'images/work/combat_melee.jpg',
        ],
        'trading-and-radio' => [
            'title' => 'Trading & the radio',
            'summary' => 'Trade posts, swapping goods and talking to other survivors.',
            'icon' => 'chat-bubble-left-right',
            'image' => 'images/work/barter.jpg',
        ],
        'brain-scorcher' => [
            'title' => 'The Brain Scorcher',
            'summary' => 'Gamble items in Pripyat for something rarer.',
            'icon' => 'sparkles',
            'image' => 'images/work/brain_scorcher.jpg',
        ],
    ],

];
