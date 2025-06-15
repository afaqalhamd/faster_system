<?php

return [
    'credentials' => [
        'file' => storage_path('app/authenticationapp-86aae-2d3203e82a6d.json'),
    ],
    'database' => [
        'url' => env('FIREBASE_DATABASE_URL', 'https://authenticationapp-86aae.firebaseio.com'),
    ],
    'messaging' => [
        'priority' => 'high',
        'time_to_live' => 0,  // immediate delivery
        'direct_boot_ok' => true
    ],
    'dynamic_links' => [
        'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN', ''),
    ],
    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_DEFAULT_BUCKET', ''),
    ],
    'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),
];
