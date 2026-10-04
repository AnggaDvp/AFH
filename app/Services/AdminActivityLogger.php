<?php

namespace App\Services;

use App\Models\AdminActivityLog;
use App\Models\User;

class AdminActivityLogger
{
    public function record(
        ?User $admin,
        string $action,
        ?string $entityType = null,
        int|string|null $entityId = null,
        array $metadata = []
    ): AdminActivityLog {
        return AdminActivityLog::create([
            'admin_id' => $admin?->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId === null ? null : (string) $entityId,
            'metadata' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }
}
