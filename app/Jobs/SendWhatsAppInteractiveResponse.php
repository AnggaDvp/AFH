<?php

namespace App\Jobs;

use App\Contracts\WhatsAppProviderInterface;
use App\Models\WhatsAppNotification;
use App\Services\WhatsAppReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppInteractiveResponse implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $notificationId)
    {
    }

    public function handle(
        WhatsAppProviderInterface $provider,
        WhatsAppReminderService $reminders
    ): void {
        if (!config('whatsapp.enabled')) {
            Log::info('Bird interactive response skipped because WHATSAPP_ENABLED=false.', [
                'notification_id' => $this->notificationId,
            ]);

            return;
        }

        $notification = WhatsAppNotification::query()->find($this->notificationId);
        $url = $notification ? $reminders->publicAdminDeepLinkFor($notification) : null;
        if (!$notification || $url === null) {
            Log::info('Bird interactive response skipped because no public admin URL is available.', [
                'notification_id' => $this->notificationId,
            ]);

            return;
        }

        $messageId = $provider->sendTextMessage(
            $notification->recipient,
            'Buka WEB Admin untuk menangani customer: ' . $url
        );

        $reminders->recordStatusActivity(
            $notification,
            'whatsapp.interactive.admin_link_sent'
        );
        Log::info('Bird interactive admin link sent.', [
            'notification_id' => $notification->id,
            'provider_message_id' => $messageId,
            'conversation_id' => $notification->conversation_id,
        ]);
    }
}
