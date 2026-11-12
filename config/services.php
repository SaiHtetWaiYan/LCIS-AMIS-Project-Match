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

    /*
    | LCIS read-only API. Allows 120 requests/minute, so requests are spaced
    | 500 ms apart by default.
    */
    'lcis' => [
        'base_url' => env('LCIS_API_BASE_URL', 'http://10.10.0.243/api/v1'),
        'token' => env('LCIS_API_TOKEN'),
        'timeout' => (int) env('LCIS_API_TIMEOUT', 30),
        'connect_timeout' => (int) env('LCIS_API_CONNECT_TIMEOUT', 5),
        'max_attempts' => (int) env('LCIS_API_MAX_ATTEMPTS', 4),
        'min_request_interval_ms' => (int) env('LCIS_API_MIN_REQUEST_INTERVAL_MS', 500),
        'page_size' => 100,
    ],

    /*
    | AMIS public API. Authenticates with the X-API-Key header; keys can expire
    | and can be restricted to a domain.
    */
    'amis' => [
        'base_url' => env('AMIS_API_BASE_URL', 'https://api.amis.gov.la/api/v1/public'),
        'key' => env('AMIS_API_KEY'),
        'timeout' => (int) env('AMIS_API_TIMEOUT', 60),
        'connect_timeout' => (int) env('AMIS_API_CONNECT_TIMEOUT', 10),
        'max_attempts' => (int) env('AMIS_API_MAX_ATTEMPTS', 4),
        'min_request_interval_ms' => (int) env('AMIS_API_MIN_REQUEST_INTERVAL_MS', 250),
        'page_size' => 100,
    ],

];
