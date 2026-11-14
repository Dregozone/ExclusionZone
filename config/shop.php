<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trade Post Bazaars
    |--------------------------------------------------------------------------
    |
    | Every trade post has a bazaar that buys any item with a base price (set
    | on the admin items page) for scrip, and sells the stock listed below.
    |
    | Selling pays the base price times the sell ratio, so other survivors
    | usually pay more than the bazaar. Each Barter level above 1 adds to the
    | sale price, up to the maximum. Selling a bigger stack at once adds a
    | small bulk bonus for every item after the first, much smaller than what
    | Barter adds.
    |
    | Buying costs the base price times the buy markup, less a discount for
    | each Barter level above 1. Even at the best rates the bazaar never pays
    | more for an item than it charges.
    |
    | Every sale or purchase trains Barter: one XP for each block of scrip
    | below, rounded up.
    |
    */

    'sell_ratio_percent' => (int) env('SHOP_SELL_RATIO_PERCENT', 50),
    'barter_sell_bonus_percent_per_level' => (int) env('SHOP_BARTER_SELL_BONUS_PERCENT_PER_LEVEL', 2),
    'maximum_barter_sell_bonus_percent' => (int) env('SHOP_MAXIMUM_BARTER_SELL_BONUS_PERCENT', 50),
    'bulk_bonus_tenths_percent_per_item' => (int) env('SHOP_BULK_BONUS_TENTHS_PERCENT_PER_ITEM', 5),
    'maximum_bulk_bonus_percent' => (int) env('SHOP_MAXIMUM_BULK_BONUS_PERCENT', 10),

    'buy_markup_percent' => (int) env('SHOP_BUY_MARKUP_PERCENT', 150),
    'barter_buy_discount_percent_per_level' => (int) env('SHOP_BARTER_BUY_DISCOUNT_PERCENT_PER_LEVEL', 1),
    'maximum_barter_buy_discount_percent' => (int) env('SHOP_MAXIMUM_BARTER_BUY_DISCOUNT_PERCENT', 25),

    'scrip_per_barter_xp' => (int) env('SHOP_SCRIP_PER_BARTER_XP', 10),

    /*
    | Item keys every bazaar keeps in stock, in the order they are shown.
    */
    'stock' => [
        'sealed_rations',
        'clean_water',
        'bandages',
        'wellington_boots',
        'fishing_rod',
        'claw_hammer',
        'crowbar',
        'hard_hat',
        'cooks_apron',
        'soldering_iron',
        'padded_jacket',
        'trail_boots',
        'ghillie_cloak',
        'medic_satchel',
        'gas_mask',
        'hazmat_suit',
        'rusty_pipe',
    ],

];
