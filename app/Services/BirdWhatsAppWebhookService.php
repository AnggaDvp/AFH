<?php

namespace App\Services;

use App\Jobs\SendWhatsAppInteractiveResponse;
use App\Models\AdminActivityLog;
use App\Models\ChatConversation;
use App\Models\WhatsAppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BirdWhatsAppWebhookService
{
    private const ACTIONS = [
        'open_admin' => 'whatsapp.interactive.open_admin',
        'reply_customer' => 'whatsapp.interactive.reply_customer',
    ];

    public function __construct(
        private BirdWhatsAppProvider $provider,
        private AdminActivityLogger $activityLogger
    ) {
    }

    public function handle(array $event, string $deliveryId): string
    {
        if (
            data_get($event, 'type') !== 'whatsapp.received'
            || data_get($event, 'data.direction') !== 'inbound'
            || data_get($event, 'data.interactive_reply.type') !== 'button'
        ) {
            return 'ignored';
        }

        $slug = data_get($event, 'data.interactive_reply.button.slug');
        $replyToMessageId = data_get($event, 'data.in_reply_to_message_id');
        $from = data_get($event, 'data.from.phone_number');
        $to = data_get($event, 'data.to.phone_number');
        if (
            !is_string($slug)
            || !isset(self::ACTIONS[$slug])
            || !is_string($replyToMessageId)
            || !is_string($from)
            || !is_string($to)
        ) {
            return 'ignored';
        }

        $notification = DB::transaction(function () use (
            $deliveryId,
            $from,
            $replyToMessageId,
            $slug,
            $to
        ): WhatsAppNotification|string {
            $notification = WhatsAppNotification::query()
                ->where('provider_message_id', $replyToMessageId)
                ->lockForUpdate()
                ->first();

            if (!$notification || !$notification->conversation_id) {
                return 'ignored';
            }

            if (
                $this->provider->normalizePhoneNumber($notification->recipient)
                    !== $this->provider->normalizePhoneNumber($from)
                || $this->provider->normalizePhoneNumber((string) config('whatsapp.sender_phone'))
                    !== $this->provider->normalizePhoneNumber($to)
            ) {
                return 'ignored';
            }

            $conversation = ChatConversation::query()->find($notification->conversation_id);
            if (
                !$conversation
                || !$notification->customer_id
                || $conversation->customer_id !== $notification->customer_id
            ) {
                return 'ignored';
            }

            $duplicate = AdminActivityLog::query()
                ->where('entity_type', 'whatsapp_notification')
                ->where('entity_id', (string) $notification->id)
                ->where('metadata->webhook_id', $deliveryId)
                ->exists();
            if ($duplicate) {
                return 'duplicate';
            }

            $this->activityLogger->record(
                null,
                self::ACTIONS[$slug],
                'whatsapp_notification',
                $notification->id,
                [
                    'webhook_id' => $deliveryId,
                    'button_slug' => $slug,
                    'conversation_id' => $conversation->id,
                    'customer_id' => $conversation->customer_id,
                ]
            );

            return $notification;
        });

        if (is_string($notification)) {
            return $notification;
        }

        if ($slug === 'open_admin') {
            SendWhatsAppInteractiveResponse::dispatch($notification->id)
                ->onConnection(config('whatsapp.queue_connection', 'database'))
                ->onQueue(config('whatsapp.queue_name', 'whatsapp'));
        }

        Log::info('Bird interactive reply processed.', [
            'webhook_id' => $deliveryId,
            'button_slug' => $slug,
            'notification_id' => $notification->id,
            'conversation_id' => $notification->conversation_id,
        ]);

        return 'handled';
    }
}
