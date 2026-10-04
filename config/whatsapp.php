<?php

return [
    'enabled' => (bool) env('WHATSAPP_ENABLED', false),
    'provider' => env('WHATSAPP_PROVIDER', 'bird'),
    'bird' => [
        'api_key' => env('BIRD_API_KEY'),
        'region' => env('BIRD_REGION'),
        'whatsapp_number_id' => env('BIRD_WHATSAPP_NUMBER_ID'),
        'webhook_secret' => env('BIRD_WEBHOOK_SECRET'),
        'webhook_tolerance' => (int) env('BIRD_WEBHOOK_TOLERANCE', 300),
    ],
    'sender_phone' => env('BIRD_SENDER_PHONE', '081770643439'),
    'reminder_template_name' => env('WHATSAPP_REMINDER_TEMPLATE_NAME', ''),
    'reminder_template_language' => env('WHATSAPP_REMINDER_TEMPLATE_LANGUAGE', 'id_ID'),
    'recipients' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('WHATSAPP_RECIPIENTS', '081315379598'))
    ))),
    'queue_connection' => env('WHATSAPP_QUEUE_CONNECTION', 'database'),
    'queue_name' => env('WHATSAPP_QUEUE_NAME', 'whatsapp'),
    'deep_link_enabled' => (bool) env('WHATSAPP_DEEP_LINK_ENABLED', false),
];
