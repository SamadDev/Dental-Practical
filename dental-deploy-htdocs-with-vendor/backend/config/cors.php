<?php

/**
 * Local-network CORS — open to LAN IP origins so tablets/laptops on the clinic Wi-Fi
 * can hit the reception PC server. No public internet exposure assumed.
 */
return [
    'paths' => ['api/*', 'storage/*'],

    'allowed_methods' => ['*'],

    // Allow any 192.168.*.* / 10.*.*.* / 172.16-31.*.* origin (RFC 1918 private ranges)
    // Also allow GitHub Pages for frontend deployment
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [
        '#^https?://192\.168\.\d+\.\d+(:\d+)?$#',
        '#^https?://10\.\d+\.\d+\.\d+(:\d+)?$#',
        '#^https?://172\.(1[6-9]|2\d|3[01])\.\d+\.\d+(:\d+)?$#',
        '#^https?://localhost(:\d+)?$#',
        '#^https?://.*\.github\.io(:\d+)?$#',
    ],

    'allowed_headers'         => ['*'],
    'exposed_headers'         => [],
    'max_age'                 => 0,
    'supports_credentials'    => false,
];
