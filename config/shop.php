<?php

/**
 * The VowNook Shop catalog — the server-side source of truth. Keys match the
 * `data-name` on the storefront's Add-to-cart buttons (public/shop/index.html);
 * never trust an amount from the client. `file` is the deliverable's key on the
 * default storage disk (upload the ZIPs to `shop-products/` — they must NEVER
 * live in public/, or they'd be freely downloadable).
 */
return [

    'products' => [
        'The Invitation Suite' => [
            'name' => 'The Invitation Suite',
            'amount_cents' => 3200,
            'file' => 'shop-products/VowNook-Invitation-Suite.zip',
            'slug' => 'invitation-suite',
        ],
        'Day-Of Printables' => [
            'name' => 'Day-Of Printables',
            'amount_cents' => 2400,
            'file' => 'shop-products/VowNook-Day-Of-Printables.zip',
            'slug' => 'day-of-printables',
        ],
        'Signage Bundle' => [
            'name' => 'Signage Bundle',
            'amount_cents' => 1900,
            'file' => 'shop-products/VowNook-Signage-Bundle.zip',
            'slug' => 'signage-bundle',
        ],
        'The Vow Book Set' => [
            'name' => 'The Vow Book Set',
            'amount_cents' => 1600,
            'file' => 'shop-products/VowNook-Vow-Book-Set.zip',
            'slug' => 'vow-book-set',
        ],
        'Thank-You and Favours' => [
            'name' => 'Thank-You & Favours',
            'amount_cents' => 1400,
            'file' => 'shop-products/VowNook-Thank-You-and-Favours.zip',
            'slug' => 'thank-you-favours',
        ],
        'The Complete Stationery Collection' => [
            'name' => 'The Complete Stationery Collection',
            'amount_cents' => 5900,
            'file' => 'shop-products/VowNook-Complete-Collection.zip',
            'slug' => 'complete-collection',
        ],
    ],

    // How long a delivery download link stays valid.
    'download_link_days' => 7,

];
