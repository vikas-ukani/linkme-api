<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, SparkPost and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_SES_ACCESS_KEY_ID'),
        'secret' => env('AWS_SES_SECRET_ACCESS_KEY'),
        'region' => env('AWS_SES_DEFAULT_REGION', 'us-east-2'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'stripe' => [
        'model' => App\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'connect' => env('STRIPE_CONNECT'),
        'callback' => env('STRIPE_CALLBACK'),
        'client_id' => env('STRIPE_CLIENT_ID'),
        'currency' => "usd",
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
            'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
        ],
    ],

    'google' => [

        'client_id' => '597716925049-dbml1fsgs9n2vf4drgkjo0ljb0ann5rm.apps.googleusercontent.com',

        'client_secret' => '-YzGcqyrTLu3WvFAMcc1zKki',

        'redirect' => 'https://linkme.techvalens.com/auth/google/callback',

    ],
    'twilio' => [
        'TWILIO_ACCOUNT_SID' => env('TWILIO_ACCOUNT_SID'),
        'TWILIO_AUTH_TOKEN' => env('TWILIO_AUTH_TOKEN'),
        'TWILIO_SERVICE_SID' => env('TWILIO_SERVICE_SID'),
        'TWILIO_API_KEY_SID' => env('TWILIO_API_KEY_SID'),
        'TWILIO_API_KEY_SECRET' => env('TWILIO_API_KEY_SECRET')
    ],

];