<?php

namespace App\Http\Controllers;

use App\Models\CustomerDocument;
use App\Models\DanaTunaiApplication;
use App\Services\AdminActivityLogger;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminDanaTunaiController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(DanaTunaiApplication::STATUSES)],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $applications = DanaTunaiApplication::query()
            ->with(['customer:id,full_name,phone', 'conversation:id,status,last_message_at'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('application_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%'))
            ))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
        $applications->getCollection()->transform(fn (DanaTunaiApplication $application): array => [
            'id' => $application->id,
            'number' => $application->application_number,
            'customer' => $application->customer->full_name,
            'phone' => $application->customer->phone,
            'status' => $application->status,
            'source' => $application->source,
            'conversationId' => $application->chat_conversation_id ? (int) $application->chat_conversation_id : null,
            'lastActivity' => $application->conversation?->last_message_at?->toIso8601String()
                ?? $application->updated_at?->toIso8601String(),
            'createdAt' => $application->created_at?->toIso8601String(),
        ]);

        return Inertia::render('admin/dana-tunai/IndexPage', [
            'applications' => $applications,
            'statuses' => DanaTunaiApplication::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function show(DanaTunaiApplication $application): Response
    {
        $application->load([
            'customer.region:id,name',
            'customer.district:id,name',
            'customer.village:id,name',
            'conversation',
            'statusHistories.changedBy:id,name',
            'documents',
        ]);

        return Inertia::render('admin/dana-tunai/ShowPage', [
            'application' => [
                'id' => $application->id,
                'number' => $application->application_number,
                'status' => $application->status,
                'source' => $application->source,
                'createdAt' => $application->created_at?->toIso8601String(),
                'submittedAt' => $application->submitted_at?->toIso8601String(),
                'lastActivity' => $application->conversation?->last_message_at?->toIso8601String()
                    ?? $application->updated_at?->toIso8601String(),
                'customer' => [
                    'id' => $application->customer->id,
                    'name' => $application->customer->full_name,
                    'phone' => $application->customer->phone,
                    'region' => $application->customer->region?->name,
                    'district' => $application->customer->district?->name,
                    'village' => $application->customer->village?->name,
                    'postalCode' => $application->customer->postal_code,
                    'streetAddress' => $application->customer->street_address,
                ],
                'conversationId' => $application->chat_conversation_id ? (int) $application->chat_conversation_id : null,
                'conversationStatus' => $application->conversation?->status,
                'documents' => $application->documents->map(fn (CustomerDocument $document) => [
                    'id' => $document->id,
                    'type' => $document->document_type,
                    'filename' => $document->original_name,
                    'mime' => $document->mime_type,
                    'size' => $document->size,
                    'status' => $document->status,
                    'uploadedAt' => $document->uploaded_at?->toIso8601String(),
                    'downloadUrl' => route('admin.documents.show', $document),
                ])->values(),
                'history' => $application->statusHistories->map(fn ($history) => [
                    'from' => $history->from_status,
                    'to' => $history->to_status,
                    'changedBy' => $history->changedBy?->name,
                    'note' => $history->note,
                    'createdAt' => $history->created_at?->toIso8601String(),
                ])->values(),
            ],
            'statuses' => DanaTunaiApplication::STATUSES,
        ]);
    }

    public function updateStatus(
        Request $request,
        DanaTunaiApplication $application,
        AdminActivityLogger $activityLogger,
        AdminNotificationService $notifications
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', Rule::in(DanaTunaiApplication::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $application, $data, $activityLogger, $notifications): void {
            $locked = DanaTunaiApplication::query()->lockForUpdate()->findOrFail($application->id);
            $from = $locked->status;
            if ($from === $data['status']) {
                throw ValidationException::withMessages(['status' => 'Pilih status yang berbeda dari status saat ini.']);
            }
            $locked->update(['status' => $data['status']]);
            $locked->statusHistories()->create([
                'from_status' => $from,
                'to_status' => $data['status'],
                'changed_by' => $request->user()->id,
                'note' => $data['note'] ?? null,
                'created_at' => now(),
            ]);
            $activityLogger->record($request->user(), 'application.status_changed', 'dana_tunai_application', $locked->id, [
                'from_status' => $from,
                'to_status' => $data['status'],
            ]);
            $notifications->notifyActiveAdmins(
                'application_status_change',
                'Status Dana Tunai diperbarui',
                $locked->application_number . ' berubah dari ' . $from . ' menjadi ' . $data['status'] . '.',
                [
                    'application_id' => $locked->id,
                    'url' => '/admin/dana-tunai/' . $locked->id,
                ],
                $request->user()->id
            );
        });

        return back()->with('success', 'Status Dana Tunai berhasil diperbarui.');
    }
}
