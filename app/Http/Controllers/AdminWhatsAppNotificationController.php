<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppNotification;
use App\Services\WhatsAppReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminWhatsAppNotificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', Rule::in(['queued', 'sending', 'sent', 'failed'])],
            'event_type' => ['nullable', 'string', 'max:50'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $notifications = WhatsAppNotification::query()
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['event_type'] ?? null, fn ($query, string $event) => $query->where('event_type', $event))
            ->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (WhatsAppNotification $notification): array => [
                'id' => $notification->id,
                'eventType' => $notification->event_type,
                'recipient' => $notification->recipient,
                'templateName' => $notification->template_name,
                'referenceType' => $notification->reference_type,
                'referenceId' => $notification->reference_id,
                'status' => $notification->status,
                'attempts' => $notification->attempts,
                'providerMessageId' => $notification->provider_message_id,
                'errorMessage' => $notification->error_message,
                'sentAt' => $notification->sent_at?->toIso8601String(),
                'createdAt' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/whatsapp-notifications/IndexPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'configuration' => [
                'enabled' => (bool) config('whatsapp.enabled'),
                'provider' => (string) config('whatsapp.provider'),
                'sender' => (string) config('whatsapp.sender_phone'),
                'recipients' => config('whatsapp.recipients', []),
                'template' => (string) config('whatsapp.reminder_template_name'),
                'deepLinkEnabled' => (bool) config('whatsapp.deep_link_enabled'),
            ],
            'notifications' => $notifications,
            'filters' => $filters,
        ]);
    }

    public function retry(
        Request $request,
        WhatsAppNotification $notification,
        WhatsAppReminderService $reminders
    ): RedirectResponse {
        $queued = $reminders->retryFailed($notification, $request->user());

        if (!$queued) {
            return back()->withErrors([
                'whatsapp' => 'Reminder belum dapat dimasukkan ke antrean. Periksa konfigurasi WhatsApp.',
            ]);
        }

        return back()->with('success', 'Reminder dimasukkan kembali ke antrean.');
    }
}
