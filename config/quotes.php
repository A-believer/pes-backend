<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Service Rate per Square Metre (£)
    |--------------------------------------------------------------------------
    */
    'service_rates' => [
        'regular_cleaning'     => 2.50,
        'deep_cleaning'        => 4.50,
        'end_of_tenancy'       => 5.50,
        'move_in'              => 4.50,
        'move_out'             => 4.50,
        'post_construction'    => 7.00,
        'commercial_cleaning'  => 3.50,
        'carpet_cleaning'      => 3.00,
        'upholstery_cleaning'  => 3.50,
        'window_cleaning'      => 2.00,
        'pressure_washing'     => 2.50,
        'sanitization'         => 3.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Urgency Surcharge (fraction added on top, e.g. 0.30 = +30%)
    |--------------------------------------------------------------------------
    */
    'urgency_surcharges' => [
        'same_day'  => 0.30,
        '24_hours'  => 0.20,
        '3_days'    => 0.10,
        'flexible'  => 0.00,
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-On Flat Prices (£)
    |--------------------------------------------------------------------------
    */
    'addon_prices' => [
        'oven_cleaning'       => 40,
        'fridge_cleaning'     => 25,
        'inside_cabinets'     => 50,
        'interior_windows'    => 35,
        'laundry'             => 30,
        'ironing'             => 25,
        'external_windows'    => 60,
        'gutter_cleaning'     => 80,
        'patio_cleaning'      => 70,
        'driveway_cleaning'   => 90,
        'garden_cleanup'      => 120,
        'floor_buffing'       => 85,
        'floor_polishing'     => 100,
        'waste_removal'       => 75,
    ],

    /*
    |--------------------------------------------------------------------------
    | Quote Range Margin (±fraction, e.g. 0.10 = ±10%)
    |--------------------------------------------------------------------------
    */
    'quote_range_margin' => 0.10,

    /*
    |--------------------------------------------------------------------------
    | Minimum Quote (£) — floor to avoid unrealistically low quotes
    |--------------------------------------------------------------------------
    */
    'minimum_quote' => 50.00,

];
