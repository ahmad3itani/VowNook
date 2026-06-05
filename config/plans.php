<?php

/**
 * Subscription plans and their server-enforced limits.
 *
 * Billing wiring (Stripe + other providers) arrives in a later phase; for now
 * these caps are enforced wherever resources are created. `max_weddings` of
 * null means unlimited.
 */
return [

    'default' => 'free',

    'tiers' => [

        'free' => [
            'name' => 'Free',
            'price' => 0,
            'max_weddings' => 1,
            'max_guests_per_wedding' => 50,
            'max_collaborators_per_wedding' => 1,
            'max_gallery_photos' => 30,
        ],

        'premium' => [
            'name' => 'Premium',
            'price' => 99,
            'max_weddings' => 1,
            'max_guests_per_wedding' => 500,
            'max_collaborators_per_wedding' => 10,
            'max_gallery_photos' => 1000,
        ],

        'planner' => [
            'name' => 'Planner',
            'price' => 499,
            'max_weddings' => null,
            'max_guests_per_wedding' => null,
            'max_collaborators_per_wedding' => null,
            'max_gallery_photos' => null,
        ],
    ],
];
