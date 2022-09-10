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

    'mailgun'  => [
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses'      => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'amoCRM'   => [
        'client_secret'                => env('AMOCRM_CLIENT_SECRET', null),
        'redirect_uri'                 => env('AMOCRM_REDIRECT_URI', null),
        'subdomain'                    => env('AMOCRM_SUBDOMAIN', null),
        'mortgage_pipeline_id'         => env('AMOCRM_MORTGAGE_PIPELINE_ID', null),
        'mortgage_responsible_user_id' => env('AMOCRM_MORTGAGE_RESPONSIBLE_USER', null),
        'mortgage_first_stage_id'      => env('AMOCRM_MORTGAGE_FIRST_STAGE', null),
    ],
];
