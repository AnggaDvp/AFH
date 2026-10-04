<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
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
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'whatsapp' => [
    'enabled' => filter_var(
        env('WHATSAPP_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),
    'provider' => env('WHATSAPP_PROVIDER', 'bird'),
],

'bird' => [
    'api_key' => trim((string) env('BIRD_API_KEY', '')),

    'base_url' => rtrim(
        trim((string) env(
            'BIRD_API_URL',
            'https://us1.platform.bird.com'
        )),
        '/'
    ),

    'sender_phone' => trim(
        (string) env('BIRD_SENDER_PHONE', '')
    ),

    'recipients' => trim(
        (string) env('WHATSAPP_RECIPIENTS', '')
    ),

    'template_name' => trim(
        (string) env(
            'WHATSAPP_REMINDER_TEMPLATE_NAME',
            'afh_pengajuan_notification'
        )
    ),

    'template_language' => trim(
        (string) env(
            'WHATSAPP_REMINDER_TEMPLATE_LANGUAGE',
            'id_ID'
        )
    ),

    'queue_connection' => env(
        'WHATSAPP_QUEUE_CONNECTION',
        'database'
    ),

    'queue_name' => env(
        'WHATSAPP_QUEUE_NAME',
        'whatsapp'
    ),

    'deep_link_enabled' => filter_var(
        env('WHATSAPP_DEEP_LINK_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),
],

];