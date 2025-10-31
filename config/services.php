<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'faster-delivery-system'),
        'server_key' => env('FIREBASE_SERVER_KEY', ''),
        'sender_id' => env('FIREBASE_SENDER_ID', ''),
        'database_url' => env('FIREBASE_DATABASE_URL', 'https://faster-delivery-system-default-rtdb.firebaseio.com'),
        'storage_bucket' => env('FIREBASE_STORAGE_BUCKET', 'faster-delivery-system.appspot.com'),
        'api_key' => env('FIREBASE_API_KEY', ''),
        'auth_domain' => env('FIREBASE_AUTH_DOMAIN', 'faster-delivery-system.firebaseapp.com'),
        'app_id' => env('FIREBASE_APP_ID', ''),
        'measurement_id' => env('FIREBASE_MEASUREMENT_ID', ''),
        'vapid_key' => env('FIREBASE_VAPID_KEY', ''),
    ],

];
