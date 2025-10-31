<?php

return [
    'credentials' => [
        'file' => storage_path('app/faster-delivery-system-firebase-adminsdk.json'),
    ],
    'database' => [
        'url' => env('FIREBASE_DATABASE_URL', 'https://faster-delivery-system-default-rtdb.firebaseio.com'),
    ],
    'project_id' => env('FIREBASE_PROJECT_ID', 'faster-delivery-system'),
    'messaging' => [
        'priority' => 'high',
        'time_to_live' => 0,  // immediate delivery
        'direct_boot_ok' => true
    ],
    'dynamic_links' => [
        'default_domain' => env('FIREBASE_DYNAMIC_LINKS_DEFAULT_DOMAIN', ''),
    ],
    'storage' => [
        'default_bucket' => env('FIREBASE_STORAGE_BUCKET', 'faster-delivery-system.appspot.com'),
    ],
    'cache_store' => env('FIREBASE_CACHE_STORE', 'file'),

    // Web App Configuration
    'web_config' => [
        'apiKey' => env('FIREBASE_API_KEY'),
        'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
        'projectId' => env('FIREBASE_PROJECT_ID'),
        'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
        'messagingSenderId' => env('FIREBASE_SENDER_ID'),
        'appId' => env('FIREBASE_APP_ID'),
        'measurementId' => env('FIREBASE_MEASUREMENT_ID'),
        'vapidKey' => env('FIREBASE_VAPID_KEY'),
    ],
];
