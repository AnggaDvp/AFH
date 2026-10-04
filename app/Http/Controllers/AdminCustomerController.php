<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminCustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        $this->validateRange($filters);

        $customers = Customer::query()
            ->with(['region:id,name', 'district:id,name', 'village:id,name'])
            ->withCount(['creditApplications', 'danaTunaiApplications', 'conversations'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query
                ->where(fn (Builder $query) => $query
                    ->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')))
            ->when($filters['region_id'] ?? null, fn (Builder $query, int $regionId) => $query->where('region_id', $regionId))
            ->when($filters['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
        $customers->getCollection()->transform(fn (Customer $customer): array => [
            'id' => $customer->id,
            'name' => $customer->full_name,
            'phone' => $customer->phone,
            'region' => $customer->region?->name,
            'district' => $customer->district?->name,
            'village' => $customer->village?->name,
            'createdAt' => $customer->created_at?->toIso8601String(),
            'creditApplicationCount' => (int) $customer->credit_applications_count,
            'danaTunaiApplicationCount' => (int) $customer->dana_tunai_applications_count,
            'conversationCount' => (int) $customer->conversations_count,
        ]);

        return Inertia::render('admin/customers/IndexPage', [
            'customers' => $customers,
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    public function show(Customer $customer): Response
    {
        $customer->load(['region:id,name', 'district:id,name', 'village:id,name']);
        $conversations = $customer->conversations()
            ->with(['motorProduct:id,name', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->paginate(15, ['*'], 'conversations_page')
            ->withQueryString();
        $creditApplications = $customer->creditApplications()
            ->with('motorProduct:id,name')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'credit_page')
            ->withQueryString()
            ->through(fn ($application) => [
                'id' => $application->id,
                'number' => $application->application_number,
                'motor' => $application->motorProduct?->name,
                'status' => $application->status,
                'createdAt' => $application->created_at?->toIso8601String(),
            ]);
        $danaTunaiApplications = $customer->danaTunaiApplications()
            ->with('conversation:id,context_type,status')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'dana_page')
            ->withQueryString()
            ->through(fn ($application) => [
                'id' => $application->id,
                'number' => $application->application_number,
                'status' => $application->status,
                'createdAt' => $application->created_at?->toIso8601String(),
                'conversationId' => $application->chat_conversation_id ? (int) $application->chat_conversation_id : null,
            ]);
        $documents = $customer->documents()->orderByDesc('uploaded_at')
            ->paginate(10, ['*'], 'documents_page')
            ->withQueryString()
            ->through(fn ($document) => [
                'id' => $document->id,
                'type' => $document->document_type,
                'name' => $document->original_name,
                'mimeType' => $document->mime_type,
                'size' => $document->size,
                'status' => $document->status,
                'uploadedAt' => $document->uploaded_at?->toIso8601String(),
                'downloadUrl' => route('admin.documents.show', $document),
            ]);
        $activity = collect();
        $customer->creditApplications()->orderByDesc('created_at')->limit(5)->get()
            ->each(fn ($item) => $activity->push([
                'at' => $item->created_at?->toIso8601String(),
                'label' => 'Pengajuan kredit ' . $item->application_number . ' dibuat',
                'url' => route('admin.applications.show', $item),
            ]));
        $customer->danaTunaiApplications()->orderByDesc('created_at')->limit(5)->get()
            ->each(fn ($item) => $activity->push([
                'at' => $item->created_at?->toIso8601String(),
                'label' => 'Application Dana Tunai ' . $item->application_number . ' dibuat',
                'url' => route('admin.dana-tunai.show', $item),
            ]));
        $customer->conversations()->orderByDesc('last_message_at')->limit(5)->get()
            ->each(fn ($item) => $activity->push([
                'at' => $item->last_message_at?->toIso8601String() ?? $item->created_at?->toIso8601String(),
                'label' => $item->context_type === 'motor' ? 'Percakapan Motor Honda' : 'Percakapan Dana Tunai',
                'url' => route('admin.chat.show', $item),
            ]));
        $customer->documents()->orderByDesc('uploaded_at')->limit(5)->get()
            ->each(fn ($item) => $activity->push([
                'at' => $item->uploaded_at?->toIso8601String(),
                'label' => 'Dokumen ' . $item->document_type . ' diunggah',
                'url' => route('admin.documents.show', $item),
            ]));

        return Inertia::render('admin/customers/ShowPage', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'phone' => $customer->phone,
                'region' => $customer->region?->name,
                'district' => $customer->district?->name,
                'village' => $customer->village?->name,
                'postalCode' => $customer->postal_code,
                'streetAddress' => $customer->street_address,
                'createdAt' => $customer->created_at?->toIso8601String(),
            ],
            'creditApplications' => $creditApplications,
            'danaTunaiApplications' => $danaTunaiApplications,
            'conversations' => $conversations->through(fn ($conversation) => [
                'id' => $conversation->id,
                'contextType' => $conversation->context_type,
                'service' => $conversation->context_type === 'motor' ? 'Motor Honda' : 'Dana Tunai / Gadai BPKB',
                'motor' => $conversation->motorProduct?->name,
                'status' => $conversation->status,
                'lastMessage' => $conversation->latestMessage
                    ? ($conversation->latestMessage->body ?: 'Lampiran')
                    : 'Belum ada pesan',
                'lastActivity' => $conversation->last_message_at?->toIso8601String(),
            ]),
            'documents' => $documents,
            'recentActivity' => $activity->sortByDesc('at')->take(10)->values(),
        ]);
    }

    private function validateRange(array $filters): void
    {
        if (($filters['from'] ?? null) && ($filters['to'] ?? null)
            && $filters['from'] > $filters['to']) {
            throw ValidationException::withMessages([
                'to' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            ]);
        }
    }
}
