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
use Throwable;

class SendWhatsAppReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $notificationId)
    {
    }

    public function handle(WhatsAppProviderInterface $provider, WhatsAppReminderService $reminders): void
    {
        $notification = WhatsAppNotification::query()->find($this->notificationId);
        if (!$notification || !in_array($notification->status, ['queued', 'sending'], true)) {
            return;
        }
        if (!config('whatsapp.enabled')) {
            Log::info('WhatsApp reminder skipped because WHATSAPP_ENABLED=false.', [
                'notification_id' => $notification->id,
            ]);

            return;
        }

        $claim = WhatsAppNotification::query()->whereKey($notification->id);
        if ($this->job && $this->attempts() > 1) {
            $connection = $this->connection ?: config('whatsapp.queue_connection', 'database');
            $retryAfter = (int) config('queue.connections.' . $connection . '.retry_after', 90);
            $staleBefore = now()->subSeconds(max(1, $retryAfter - 1));

            $claim->where(function ($query) use ($staleBefore) {
                $query->where('status', 'queued')
                    ->orWhere(function ($stale) use ($staleBefore) {
                        $stale->where('status', 'sending')
                            ->where('updated_at', '<=', $staleBefore);
                    });
            });
        } else {
            $claim->where('status', 'queued');
        }

        $claimed = $claim->update(['status' => 'sending', 'updated_at' => now()]);
        if ($claimed !== 1) {
            return;
        }

        $notification->refresh();
        $notification->increment('attempts');
        try {
            $providerMessageId = $provider->sendTemplateMessage(
                $notification->recipient,
                (string) $notification->template_name,
                (string) config('whatsapp.reminder_template_language'),
                $reminders->templateParametersFor($notification)
            );
            $notification->forceFill([
                'status' => 'sent',
                'provider_message_id' => $providerMessageId,
                'error_message' => null,
                'sent_at' => now(),
            ])->save();
            $reminders->recordStatusActivity($notification, 'whatsapp.reminder_sent');
        } catch (Throwable $exception) {
            $notification->forceFill(['status' => 'queued'])->save();
            Log::warning('WhatsApp reminder attempt failed.', [
                'notification_id' => $notification->id,
                'attempt' => $notification->attempts,
                'exception_type' => get_class($exception),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $notification = WhatsAppNotification::query()->find($this->notificationId);
        if (!$notification || !in_array($notification->status, ['queued', 'sending'], true)) {
            return;
        }

        $notification->forceFill([
            'status' => 'failed',
            'error_message' => 'Pengiriman WhatsApp gagal setelah beberapa percobaan.',
        ])->save();
        app(WhatsAppReminderService::class)
            ->recordStatusActivity($notification, 'whatsapp.reminder_failed');

        Log::error('WhatsApp reminder exhausted its retries.', [
            'notification_id' => $notification->id,
            'exception_type' => get_class($exception),
        ]);
    }
}
