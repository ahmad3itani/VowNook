<?php

/**
 * Subscription plans and their server-enforced limits.
 *
 * `audience` segments which account type sees a tier on the plan page
 * (couples never see the planner tier and vice-versa; vendors are
 * commission-based and see no SaaS tier). `features` are boolean capability
 * flags enforced server-side. A null limit means unlimited.
 */
return [

    'default' => 'free',

    'tiers' => [

        'free' => [
            'name' => 'Free',
            'price' => 0,
            'audience' => 'couple',
            'max_weddings' => 1,
            'max_guests_per_wedding' => 25,
            'max_collaborators_per_wedding' => 0,
            'max_gallery_photos' => 15,
            'features' => [
                'ai' => false,
                'website_publish' => false,
                'seating' => false,
                'registry' => false,
                'events' => false,
                'travel' => false,
                'broadcast' => false,
                'save_the_dates' => false,
                'subdomain' => false,
            ],
        ],

        'premium' => [
            'name' => 'Atelier',
            'price' => 99,
            'audience' => 'couple',
            'max_weddings' => 1,
            'max_guests_per_wedding' => 500,
            'max_collaborators_per_wedding' => 10,
            'max_gallery_photos' => 1000,
            'features' => [
                'ai' => true,
                'website_publish' => true,
                'seating' => true,
                'registry' => true,
                'events' => true,
                'travel' => true,
                'broadcast' => true,
                'save_the_dates' => true,
                'subdomain' => true,
            ],
        ],

        'planner' => [
            'name' => 'Planner HQ',
            'price' => 499,
            'audience' => 'planner',
            'max_weddings' => null,
            'max_guests_per_wedding' => null,
            'max_collaborators_per_wedding' => null,
            'max_gallery_photos' => null,
            'features' => [
                'ai' => true,
                'website_publish' => true,
                'seating' => true,
                'registry' => true,
                'events' => true,
                'travel' => true,
                'broadcast' => true,
                'save_the_dates' => true,
                'subdomain' => true,
            ],
        ],
    ],
];
