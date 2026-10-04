<?php

namespace App\Services;

use App\Jobs\SendWhatsAppReminder;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\WhatsAppNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class WhatsAppReminderService
{
    public function queueCustomerRegistration(Customer $customer): int
    {
        return $this->queue('new_customer_registration', 'customer', $customer->id);
    }

    public function queueMotorInterest(ChatConversation $conversation): int
    {
        return $this->queue('new_motor_interest', 'chat_conversation', $conversation->id);
    }

    public function queueDanaTunaiInterest(ChatConversation $conversation): int
    {
        return $this->queue('new_dana_tunai_interest', 'chat_conversation', $conversation->id);
    }

    public function queueCreditApplication(CreditApplication $application): int
    {
        if ($application->status !== 'submitted') {
            return 0;
        }

        return $this->queue('new_credit_application', 'credit_application', $application->id);
    }

    public function queueNeedDocuments(CreditApplication $application): int
    {
        if ($application->status !== 'need_documents') {
            return 0;
        }

        return $this->queue('need_documents', 'credit_application', $application->id);
    }

    public function queueNewChatMessage(ChatMessage $message): int
    {
        if ($message->sender_type !== 'customer') {
            return 0;
        }

        return $this->queue('new_chat_message', 'chat_message', $message->id);
    }

    public function queueNewAttachment(ChatMessage $message): int
    {
        if ($message->sender_type !== 'customer' || !$message->attachments()->exists()) {
            return 0;
        }

        return $this->queue('new_attachment', 'chat_message', $message->id);
    }

    /**
     * Re-dispatches the same failed notification, preserving its idempotency key.
     */
    public function retryFailed(WhatsAppNotification $notification, User $admin): bool
    {
        if (!config('whatsapp.enabled')) {
            throw ValidationException::withMessages([
                'whatsapp' => 'Aktifkan integrasi WhatsApp sebelum mencoba ulang reminder.',
            ]);
        }

        $notification = DB::transaction(function () use ($notification, $admin): WhatsAppNotification {
            $locked = WhatsAppNotification::query()
                ->lockForUpdate()
                ->findOrFail($notification->id);

            if ($locked->status !== 'failed') {
                throw ValidationException::withMessages([
                    'whatsapp' => 'Hanya reminder gagal yang dapat dicoba ulang.',
                ]);
            }

            $locked->forceFill([
                'status' => 'queued',
                'error_message' => null,
                'provider_message_id' => null,
                'sent_at' => null,
            ])->save();
            $this->recordActivity($locked, 'whatsapp.reminder_retry_requested', $admin);

            return $locked;
        });

        return $this->dispatch($notification);
    }

    /** @return array<string, string> */
    public function templateParametersFor(WhatsAppNotification $notification): array
    {
        $variables = $notification->payload['variables'] ?? [];

        foreach (['customer_name', 'service_type', 'customer_phone', 'activity_summary'] as $key) {
            if (!isset($variables[$key]) || !is_string($variables[$key])) {
                throw new \RuntimeException('WhatsApp reminder template data is incomplete.');
            }
        }

        return [
            'customer_name' => $variables['customer_name'],
            'service_type' => $variables['service_type'],
            'customer_phone' => $variables['customer_phone'],
            'activity_summary' => $variables['activity_summary'],
        ];
    }

    public function adminDeepLinkFor(WhatsAppNotification $notification): ?string
    {
        if ($notification->conversation_id) {
            return $this->absoluteAdminRoute('admin.chat.show', [
                'conversation' => $notification->conversation_id,
            ]);
        }

        if ($notification->application_id) {
            return $this->absoluteAdminRoute('admin.applications.show', [
                'application' => $notification->application_id,
            ]);
        }

        return null;
    }

    public function publicAdminDeepLinkFor(WhatsAppNotification $notification): ?string
    {
        if (!config('whatsapp.deep_link_enabled')) {
            return null;
        }

        $url = $this->adminDeepLinkFor($notification);
        if ($url === null) {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme !== 'https' || in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return null;
        }

        return $url;
    }

    private function absoluteAdminRoute(string $name, array $parameters): string
    {
        return rtrim((string) config('app.url'), '/')
            . route($name, $parameters, false);
    }

    private function queue(string $eventType, string $referenceType, int|string $referenceId): int
    {
        try {
            $payload = $this->payloadFor($eventType, $referenceType, (string) $referenceId);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp reminder data could not be prepared.', [
                'event_type' => $eventType,
                'reference_type' => $referenceType,
                'reference_id' => (string) $referenceId,
                'exception_type' => get_class($exception),
            ]);

            return 0;
        }

        $created = 0;
        foreach (config('whatsapp.recipients', []) as $recipient) {
            try {
                $recipient = (string) $recipient;
                $key = hash('sha256', implode('|', [
                    $eventType,
                    $referenceType,
                    (string) $referenceId,
                    $recipient,
                ]));
                $notification = WhatsAppNotification::query()->firstOrCreate(
                    ['idempotency_key' => $key],
                    [
                        'event_type' => $eventType,
                        'recipient' => $recipient,
                        'message_type' => 'template',
                        'template_name' => config('whatsapp.reminder_template_name') ?: null,
                        'reference_type' => $referenceType,
                        'reference_id' => (string) $referenceId,
                        'payload' => $payload,
                        'customer_id' => $payload['customer_id'],
                        'conversation_id' => $payload['conversation_id'],
                        'application_id' => $payload['application_id'],
                        'motor_product_id' => $payload['motor_product_id'],
                        'status' => 'queued',
                    ]
                );

                if (!$notification->wasRecentlyCreated) {
                    continue;
                }

                $created++;
                $this->recordActivity($notification, 'whatsapp.reminder_queued');
                if (!config('whatsapp.enabled')) {
                    Log::info('WhatsApp reminder skipped because WHATSAPP_ENABLED=false.', [
                        'event_type' => $eventType,
                        'notification_id' => $notification->id,
                        'recipient' => $recipient,
                    ]);

                    continue;
                }

                $this->dispatch($notification);
            } catch (Throwable $exception) {
                Log::warning('WhatsApp reminder could not be queued.', [
                    'event_type' => $eventType,
                    'reference_type' => $referenceType,
                    'reference_id' => (string) $referenceId,
                    'exception_type' => get_class($exception),
                ]);
            }
        }

        return $created;
    }

    private function dispatch(WhatsAppNotification $notification): bool
    {
        if (!$this->providerConfigurationIsComplete()) {
            $this->markEnqueueFailure($notification, 'Konfigurasi WhatsApp atau template belum lengkap.');

            return false;
        }

        $connection = config('whatsapp.queue_connection', 'database');
        if ($connection === 'sync') {
            $this->markEnqueueFailure($notification, 'Reminder tidak dapat dimasukkan ke antrean.');
            Log::warning('WhatsApp reminder requires an asynchronous queue connection.', [
                'notification_id' => $notification->id,
            ]);

            return false;
        }

        try {
            SendWhatsAppReminder::dispatch($notification->id)
                ->onConnection($connection)
                ->onQueue(config('whatsapp.queue_name', 'whatsapp'));

            return true;
        } catch (Throwable $exception) {
            $this->markEnqueueFailure($notification, 'Reminder tidak dapat dimasukkan ke antrean.');
            Log::warning('WhatsApp reminder could not be dispatched to the queue.', [
                'notification_id' => $notification->id,
                'exception_type' => get_class($exception),
            ]);

            return false;
        }
    }

    private function providerConfigurationIsComplete(): bool
    {
        return (string) config('whatsapp.provider') === 'bird'
            && (string) config('whatsapp.bird.api_key') !== ''
            && (string) config('whatsapp.bird.region') !== ''
            && (string) config('whatsapp.sender_phone') !== ''
            && (string) config('whatsapp.reminder_template_name') !== ''
            && (string) config('whatsapp.reminder_template_language') !== '';
    }

    private function payloadFor(string $eventType, string $referenceType, string $referenceId): array
    {
        $customer = null;
        $conversation = null;
        $application = null;
        $motor = null;
        $service = '';
        $detail = '';

        if ($referenceType === 'customer') {
            $customer = Customer::query()->findOrFail($referenceId);
            $service = 'Pendaftaran Customer';
            $detail = 'Customer baru mendaftar dan siap ditindaklanjuti.';
        } elseif ($referenceType === 'chat_conversation') {
            $conversation = ChatConversation::with([
                'customer:id,full_name,phone',
                'motorProduct:id,name',
            ])->findOrFail($referenceId);
            $customer = $conversation->customer;
            $motor = $conversation->motorProduct;
            if ($eventType === 'new_dana_tunai_interest') {
                $service = 'Dana Tunai';
                $detail = 'Customer mengajukan Dana Tunai. Internal chat tersedia.';
            } else {
                $service = 'Motor';
                $detail = 'Minat pada motor ' . ($motor?->name ?? 'Honda') . '. Internal chat tersedia.';
            }
        } elseif ($referenceType === 'credit_application') {
            $application = CreditApplication::with([
                'customer:id,full_name,phone',
                'motorProduct:id,name',
            ])->findOrFail($referenceId);
            $customer = $application->customer;
            $motor = $application->motorProduct;
            $conversation = ChatConversation::query()
                ->where('customer_id', $customer->id)
                ->where('context_type', 'motor')
                ->when(
                    $application->motor_product_id,
                    fn ($query, int $motorId) => $query->where('motor_product_id', $motorId)
                )
                ->latest('last_message_at')
                ->first();
            $service = 'Motor';
            $detail = $eventType === 'need_documents'
                ? 'Pengajuan ' . $application->application_number
                    . ($motor ? ' - ' . $motor->name : '')
                    . '. Customer perlu melengkapi dokumen. Silakan buka Admin Panel.'
                : 'Pengajuan ' . $application->application_number
                    . ($motor ? ' - ' . $motor->name : '')
                    . ', status submitted.';
        } elseif ($referenceType === 'chat_message') {
            $message = ChatMessage::with([
                'conversation.customer:id,full_name,phone',
                'conversation.motorProduct:id,name',
                'attachments:id,message_id,attachment_type',
            ])->findOrFail($referenceId);
            $conversation = $message->conversation;
            $customer = $conversation->customer;
            $motor = $conversation->motorProduct;

            if ($conversation->context_type === 'dana_tunai') {
                $service = 'Dana Tunai';
            } else {
                $service = 'Motor';
            }

            if ($eventType === 'new_attachment') {
                $attachmentTypes = $message->attachments->pluck('attachment_type')->unique();
                $kind = $attachmentTypes->contains('image') && $attachmentTypes->count() === 1
                    ? 'foto'
                    : 'dokumen';
                $detail = 'Customer mengirim ' . $kind . ' baru di chat. Internal chat tersedia.';
            } else {
                $detail = 'Customer mengirim pesan baru di chat. Internal chat tersedia.';
            }
        } else {
            throw new \RuntimeException('WhatsApp reminder reference is invalid.');
        }

        if (!$customer) {
            throw new \RuntimeException('WhatsApp reminder customer is missing.');
        }

        return [
            'customer_id' => $customer->id,
            'conversation_id' => $conversation?->id,
            'application_id' => $application?->id,
            'motor_product_id' => $motor?->id,
            'admin_url' => $conversation
                ? $this->absoluteAdminRoute('admin.chat.show', ['conversation' => $conversation->id])
                : ($application
                    ? $this->absoluteAdminRoute('admin.applications.show', ['application' => $application->id])
                    : null),
            'variables' => [
                'customer_name' => $customer->full_name,
                'customer_phone' => $customer->phone,
                'service_type' => $service,
                'activity_summary' => $detail,
            ],
        ];
    }

    private function markEnqueueFailure(WhatsAppNotification $notification, string $message): void
    {
        $notification->forceFill([
            'status' => 'failed',
            'error_message' => $message,
        ])->save();
        $this->recordActivity($notification, 'whatsapp.reminder_enqueue_failed');
    }

    private function recordActivity(
        WhatsAppNotification $notification,
        string $action,
        ?User $admin = null
    ): void {
        try {
            app(AdminActivityLogger::class)->record(
                $admin,
                $action,
                'whatsapp_notification',
                $notification->id,
                [
                    'event_type' => $notification->event_type,
                    'recipient' => $notification->recipient,
                ]
            );
        } catch (Throwable $exception) {
            Log::warning('WhatsApp activity could not be recorded.', [
                'notification_id' => $notification->id,
                'exception_type' => get_class($exception),
            ]);
        }
    }

    public function recordStatusActivity(
        WhatsAppNotification $notification,
        string $action,
        ?User $admin = null
    ): void {
        $this->recordActivity($notification, $action, $admin);
    }
}
