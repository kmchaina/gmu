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

    /*
     | NREIMS single sign-on (Laravel Passport, authorization-code grant).
     | In NREIMS, register an oauth_clients row with redirect = NREIMS_REDIRECT_URI
     | and is_first_party = 1 so staff are not shown a consent screen.
     | Sign-in with NREIMS appears on the login page once the client id is set.
     */
    'nreims' => [
        'base_url'      => env('NREIMS_BASE_URL'),
        'client_id'     => env('NREIMS_CLIENT_ID'),
        'client_secret' => env('NREIMS_CLIENT_SECRET'),
        'redirect'      => env('NREIMS_REDIRECT_URI'),
        // New accounts start as applicants; GMU changes roles under People and roles.
        'default_role'  => env('NREIMS_DEFAULT_ROLE', 'pi'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
