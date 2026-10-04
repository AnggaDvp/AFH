<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminNotificationController extends Controller
{
    public function index(Request $request, AdminNotificationService $notificationService): Response
    {
        $notifications = AdminNotification::query()
            ->where('recipient_admin_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AdminNotification $notification): array => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'url' => is_string($notification->data['url'] ?? null)
                    && str_starts_with($notification->data['url'], '/admin/')
                    ? $notification->data['url']
                    : null,
                'readAt' => $notification->read_at?->toIso8601String(),
                'createdAt' => $notification->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/notifications/IndexPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'notifications' => $notifications,
            'unreadCount' => $notificationService->unreadCountFor($request->user()),
        ]);
    }

    public function markRead(Request $request, AdminNotification $notification): RedirectResponse
    {
        $ownedNotification = AdminNotification::query()
            ->where('recipient_admin_id', $request->user()->id)
            ->findOrFail($notification->id);

        if (!$ownedNotification->read_at) {
            $ownedNotification->forceFill(['read_at' => now()])->save();
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        AdminNotification::query()
            ->where('recipient_admin_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back();
    }
}
