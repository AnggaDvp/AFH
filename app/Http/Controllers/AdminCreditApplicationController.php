<?php

namespace App\Http\Controllers;

use App\Models\CreditApplication;
use App\Models\CustomerDocument;
use App\Services\AdminActivityLogger;
use App\Services\AdminNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCreditApplicationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(CreditApplication::STATUSES)],
            'motor_id' => ['nullable', 'integer', 'exists:motor_products,id'],
            'source' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $applications = CreditApplication::query()
            ->with(['customer:id,full_name,phone', 'motorProduct:id,name', 'brochureItem:id,cash_price'])
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('application_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', fn ($customer) => $customer
                        ->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%'))
            ))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['motor_id'] ?? null, fn ($query, int $motorId) => $query->where('motor_product_id', $motorId))
            ->when($filters['source'] ?? null, fn ($query, string $source) => $query->where('source', $source))
            ->when($filters['from'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
        $applications->getCollection()->transform(fn (CreditApplication $application): array => [
            'id' => $application->id,
            'number' => $application->application_number,
            'customer' => $application->customer->full_name,
            'phone' => $application->customer->phone,
            'motor' => $application->motorProduct?->name,
            'cashPrice' => $application->cash_price ?? $application->brochureItem?->cash_price,
            'downPayment' => $application->down_payment,
            'tenorMonths' => $application->tenor_months,
            'installmentSource' => $application->installment_source,
            'status' => $application->status,
            'source' => $application->source,
            'createdAt' => $application->created_at?->toIso8601String(),
            'submittedAt' => $application->submitted_at?->toIso8601String(),
            'updatedAt' => $application->updated_at?->toIso8601String(),
        ]);

        return Inertia::render('admin/applications/IndexPage', [
            'applications' => $applications,
            'motors' => \App\Models\MotorProduct::query()->orderBy('name')->get(['id', 'name']),
            'statuses' => CreditApplication::STATUSES,
            'filters' => $filters,
        ]);
    }

    public function show(CreditApplication $application): Response
    {
        $application->load([
            'customer.region:id,name',
            'customer.district:id,name',
            'customer.village:id,name',
            'motorProduct:id,name,family',
            'brochureItem.brochure:id,period_label,source_name',
            'statusHistories.changedBy:id,name',
            'documents',
        ]);
        $conversations = $application->customer->conversations()
            ->with(['motorProduct:id,name', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->limit(10)
            ->get();

        return Inertia::render('admin/applications/ShowPage', [
            'application' => [
                'id' => $application->id,
                'number' => $application->application_number,
                'status' => $application->status,
                'source' => $application->source,
                'createdAt' => $application->created_at?->toIso8601String(),
                'submittedAt' => $application->submitted_at?->toIso8601String(),
                'updatedAt' => $application->updated_at?->toIso8601String(),
                'customer' => [
                    'id' => $application->customer->id,
                    'name' => $application->customer->full_name,
                    'phone' => $application->customer->phone,
                    'region' => $application->customer->region?->name,
                    'district' => $application->customer->district?->name,
                    'village' => $application->customer->village?->name,
                ],
                'motor' => $application->motorProduct ? [
                    'name' => $application->motorProduct->name,
                    'family' => $application->motorProduct->family,
                    'brochure' => $application->brochureItem?->brochure?->period_label,
                    'cashPrice' => $application->cash_price ?? $application->brochureItem?->cash_price,
                ] : null,
                'financing' => [
                    'downPayment' => $application->down_payment,
                    'tenorMonths' => $application->tenor_months,
                    'installmentAmount' => $application->installment_amount,
                    'installmentSource' => $application->installment_source,
                ],
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
                'conversations' => $conversations->map(fn ($conversation) => [
                    'id' => $conversation->id,
                    'service' => $conversation->context_type === 'motor' ? 'Motor Honda' : 'Dana Tunai / Gadai BPKB',
                    'motor' => $conversation->motorProduct?->name,
                    'status' => $conversation->status,
                    'lastMessage' => $conversation->latestMessage?->body ?: 'Lampiran',
                ])->values(),
                'history' => $application->statusHistories->map(fn ($history) => [
                    'from' => $history->from_status,
                    'to' => $history->to_status,
                    'changedBy' => $history->changedBy?->name,
                    'note' => $history->note,
                    'createdAt' => $history->created_at?->toIso8601String(),
                ])->values(),
            ],
            'statuses' => CreditApplication::STATUSES,
        ]);
    }

    public function updateStatus(
        Request $request,
        CreditApplication $application,
        AdminActivityLogger $activityLogger,
        AdminNotificationService $notifications
    ): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(CreditApplication::STATUSES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        DB::transaction(function () use ($request, $application, $data, $activityLogger, $notifications): void {
            $locked = CreditApplication::query()->lockForUpdate()->findOrFail($application->id);
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
            $activityLogger->record($request->user(), 'application.status_changed', 'credit_application', $locked->id, [
                'from_status' => $from,
                'to_status' => $data['status'],
            ]);
            $notifications->notifyActiveAdmins(
                'application_status_change',
                'Status pengajuan kredit diperbarui',
                $locked->application_number . ' berubah dari ' . $from . ' menjadi ' . $data['status'] . '.',
                [
                    'application_id' => $locked->id,
                    'url' => '/admin/pengajuan/' . $locked->id,
                ],
                $request->user()->id
            );
        });

        return back()->with('success', 'Status pengajuan berhasil diperbarui.');
    }
}
