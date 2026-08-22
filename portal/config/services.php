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

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        // Finding M14: the previous default named a retired model id.
        'model'   => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
    ],

    // SMS alerts to PIs in the field (slide 10 of the GMU concept). Without a
    // gateway configured the null driver logs instead of sending.
    'sms' => [
        'gateway'   => env('GMU_SMS_GATEWAY', 'null'),
        'username'  => env('GMU_SMS_USERNAME'),
        'key'       => env('GMU_SMS_KEY'),
        'sender_id' => env('GMU_SMS_SENDER_ID', 'NIMR'),
        'endpoint'  => env('GMU_SMS_ENDPOINT', 'https://api.africastalking.com/version1/messaging'),
    ],

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

];
