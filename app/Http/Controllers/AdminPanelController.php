<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\CreditApplication;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\DanaTunaiApplication;
use App\Models\MotorProduct;
use App\Models\WhatsAppNotification;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminPanelController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'period' => ['nullable', Rule::in(['today', '7days', 'month', 'custom'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);
        if (($filters['from'] ?? null) && ($filters['to'] ?? null) && $filters['from'] > $filters['to']) {
            throw ValidationException::withMessages(['to' => 'Tanggal akhir harus setelah tanggal awal.']);
        }
        $period = $filters['period'] ?? 'month';
        $now = now();
        [$from, $to] = match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'custom' => [
                isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : $now->copy()->startOfMonth(),
                isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : $now->copy()->endOfDay(),
            ],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
        };
        $range = [$from, $to];
        $creditStatuses = CreditApplication::query()
            ->whereBetween('created_at', $range)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $danaStatuses = DanaTunaiApplication::query()
            ->whereBetween('created_at', $range)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $whatsappStatuses = WhatsAppNotification::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $unreadMessages = fn ($query) => $query->where('sender_type', 'customer')->whereNull('read_at');

        return Inertia::render('admin/IndexPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'filters' => [
                'period' => $period,
                'from' => $period === 'custom' ? $filters['from'] ?? $from->toDateString() : null,
                'to' => $period === 'custom' ? $filters['to'] ?? $to->toDateString() : null,
            ],
            'periodLabel' => $from->format('d M Y') . ' – ' . $to->format('d M Y'),
            'kpis' => [
                'customers' => [
                    'total' => Customer::count(),
                    'inPeriod' => Customer::whereBetween('created_at', $range)->count(),
                    'today' => Customer::whereDate('created_at', $now->toDateString())->count(),
                    'thisMonth' => Customer::whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count(),
                ],
                'motors' => [
                    'active' => MotorProduct::where('status', 'active')->count(),
                    'interestInPeriod' => ChatConversation::where('context_type', 'motor')->whereBetween('created_at', $range)->count(),
                    'interestToday' => ChatConversation::where('context_type', 'motor')->whereDate('created_at', $now->toDateString())->count(),
                    'interestThisMonth' => ChatConversation::where('context_type', 'motor')
                        ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])->count(),
                ],
                'creditApplications' => [
                    'totalInPeriod' => array_sum($creditStatuses->map(fn ($count) => (int) $count)->all()),
                    'byStatus' => collect(CreditApplication::STATUSES)
                        ->mapWithKeys(fn ($status) => [$status => (int) ($creditStatuses[$status] ?? 0)]),
                    'pendingReview' => (int) ($creditStatuses['submitted'] ?? 0)
                        + (int) ($creditStatuses['under_review'] ?? 0)
                        + (int) ($creditStatuses['need_documents'] ?? 0),
                ],
                'danaTunai' => [
                    'totalInPeriod' => array_sum($danaStatuses->map(fn ($count) => (int) $count)->all()),
                    'byStatus' => collect(DanaTunaiApplication::STATUSES)
                        ->mapWithKeys(fn ($status) => [$status => (int) ($danaStatuses[$status] ?? 0)]),
                ],
                'chat' => [
                    'open' => ChatConversation::where('status', 'open')->count(),
                    'unread' => ChatConversation::whereHas('messages', $unreadMessages)->count(),
                    'activeInPeriod' => ChatConversation::whereBetween('last_message_at', $range)->count(),
                ],
                'documents' => [
                    'waitingReview' => CustomerDocument::where('status', 'uploaded')->count(),
                ],
                'whatsapp' => collect(['queued', 'sending', 'sent', 'failed'])
                    ->mapWithKeys(fn (string $status): array => [$status => (int) ($whatsappStatuses[$status] ?? 0)]),
            ],
        ]);
    }
}
