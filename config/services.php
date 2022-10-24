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
        'client_secret'                       => env('AMOCRM_CLIENT_SECRET', null),
        'redirect_uri'                        => env('AMOCRM_REDIRECT_URI', null),
        'subdomain'                           => env('AMOCRM_SUBDOMAIN', null),
        'discount_card_field_id'              => env('AMOCRM_DISCOUNT_CARD_FIELD_ID', null),
        'price_without_discount_id'           => env('AMOCRM_PRICE_WITHOUT_DISCOUNT_ID', null),
        'discount_common'                     => env('AMOCRM_DISCOUNT_COMMON', null),
        'successful_stage_id'                 => env('AMOCRM_SUCCESSFUL_STAGE_ID', null),
        'loss_stage_id'                       => env('AMOCRM_LOSS_STAGE_ID', null),
        'lead_created_at'                     => env('AMOCRM_LEAD_CREATED_AT', null),
        'conditionally_successful_stage_id'   => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID', null),
        'conditionally_successful_stage_id_1' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_1', null),
        'conditionally_successful_stage_id_2' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_2', null),
        'conditionally_successful_stage_id_3' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_3', null),
        'conditionally_successful_stage_id_4' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_4', null),
        'conditionally_successful_stage_id_5' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_5', null),
        'conditionally_successful_stage_id_6' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_6', null),
        'conditionally_successful_stage_id_7' => env('AMOCRM_CONDITIONALLY_SUCCESSFUL_STAGE_ID_7', null),
    ],
];
