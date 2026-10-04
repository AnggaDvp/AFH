<?php

namespace App\Http\Controllers;

use App\Models\CustomerDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDocumentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['uploaded', 'reviewed', 'need_replacement'])],
            'type' => ['nullable', 'string', 'max:50'],
        ]);
        $documents = CustomerDocument::query()
            ->with('customer:id,full_name,phone')
            ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                fn ($query) => $query->where('original_name', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', fn ($customer) => $customer->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%'))
            ))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('document_type', $type))
            ->orderByDesc('uploaded_at')
            ->paginate(25)
            ->withQueryString();
        $documents->getCollection()->transform(fn (CustomerDocument $document): array => [
            'id' => $document->id,
            'customerId' => $document->customer_id,
            'customer' => $document->customer->full_name,
            'phone' => $document->customer->phone,
            'type' => $document->document_type,
            'filename' => $document->original_name,
            'mime' => $document->mime_type,
            'size' => $document->size,
            'status' => $document->status,
            'uploadedAt' => $document->uploaded_at?->toIso8601String(),
            'downloadUrl' => route('admin.documents.show', $document),
        ]);

        return Inertia::render('admin/documents/IndexPage', [
            'documents' => $documents,
            'filters' => $filters,
        ]);
    }

    public function show(CustomerDocument $document): StreamedResponse
    {
        abort_unless(
            $document->disk === 'chat_private'
                && preg_match('/^documents\/' . $document->customer_id . '\/[a-f0-9-]{36}\.[a-z0-9]{1,10}$/', $document->path),
            404
        );
        $disk = Storage::disk('chat_private');
        abort_unless($disk->exists($document->path), 404);

        return $disk->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime_type,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox; frame-ancestors 'none'",
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ], 'attachment');
    }
}
