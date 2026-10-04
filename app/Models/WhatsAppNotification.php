<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppNotification extends Model
{
    protected $table = 'whatsapp_notifications';

    protected $fillable = [
        'event_type',
        'recipient',
        'message_type',
        'template_name',
        'reference_type',
        'reference_id',
        'payload',
        'customer_id',
        'conversation_id',
        'application_id',
        'motor_product_id',
        'idempotency_key',
        'status',
        'provider_message_id',
        'error_message',
        'attempts',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'customer_id' => 'integer',
        'conversation_id' => 'integer',
        'application_id' => 'integer',
        'motor_product_id' => 'integer',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];
}
