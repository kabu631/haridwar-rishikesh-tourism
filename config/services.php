<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google_ads' => [
        'conversion_label' => env('GOOGLE_ADS_CONVERSION_LABEL'),
    ],

    /*
     | Tripadvisor Content API (https://www.tripadvisor.com/developers), used by
     | `php artisan tripadvisor:import-reviews` for the homepage testimonials.
     | Location ids come from the listing URLs ("-d4868270-" → 4868270).
     */
    'tripadvisor' => [
        'key' => env('TRIPADVISOR_API_KEY'),
        'location_ids' => [
            4868270, // Haridwar Rishikesh Tourism Private Day Tours (Haridwar)
            5982072, // India Easy Trip (Rishikesh)
        ],
    ],

];
