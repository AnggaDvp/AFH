<?php

namespace App\Services;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class AdminNotificationService
{
    public function notifyActiveAdmins(
        string $type,
        string $title,
        string $message,
        array $data = [],
        ?int $exceptAdminId = null
    ): int {
        try {
            $recipients = User::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->whereIn('role', ['admin', 'staff'])
                    ->orWhere('is_admin', true))
                ->when($exceptAdminId, fn ($query) => $query->where('id', '<>', $exceptAdminId))
                ->pluck('id');

            if ($recipients->isEmpty()) {
                return 0;
            }

            $now = now();
            $rows = $recipients->map(fn (int $recipientId): array => [
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data ? json_encode($data, JSON_THROW_ON_ERROR) : null,
                'recipient_admin_id' => $recipientId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            AdminNotification::query()->insert($rows);

            return count($rows);
        } catch (Throwable $exception) {
            Log::warning('Admin notification could not be stored.', [
                'event_type' => $type,
                'exception_type' => get_class($exception),
            ]);

            return 0;
        }
    }

    public function unreadCountFor(User $admin): int
    {
        return AdminNotification::query()
            ->where('recipient_admin_id', $admin->id)
            ->whereNull('read_at')
            ->count();
    }
}
