<?php

namespace App\Http\Controllers;

use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminActivityLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = AdminActivityLog::query()
            ->with('admin:id,name')
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['entity_type'] ?? null, fn ($query, string $entityType) => $query->where('entity_type', $entityType))
            ->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AdminActivityLog $log): array => [
                'id' => $log->id,
                'admin' => $log->admin?->name ?? 'Akun admin dihapus',
                'action' => $log->action,
                'entityType' => $log->entity_type,
                'entityId' => $log->entity_id,
                'metadata' => $log->metadata,
                'createdAt' => $log->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/activity-logs/IndexPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
