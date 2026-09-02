<?php

/**
 * CORS — open to LAN IP origins so tablets/laptops on the clinic Wi-Fi
 * can hit the reception PC server, plus the production public domain.
 */
return [
    'paths' => ['api/*', 'storage/*'],

    'allowed_methods' => ['*'],

    // Whitelist: LAN private ranges, localhost, and the production public domain.
    'allowed_origins' => [],
    'allowed_origins_patterns' => [
        '#^https?://192\.168\.\d+\.\d+(:\d+)?$#',
        '#^https?://10\.\d+\.\d+\.\d+(:\d+)?$#',
        '#^https?://172\.(1[6-9]|2\d|3[01])\.\d+\.\d+(:\d+)?$#',
        '#^https?://localhost(:\d+)?$#',
        '#^https?://([a-z0-9-]+\.)?smartvisioniq\.com$#',
    ],

    'allowed_headers'         => ['*'],
    'exposed_headers'         => [],
    'max_age'                 => 0,
    'supports_credentials'    => false,
];
