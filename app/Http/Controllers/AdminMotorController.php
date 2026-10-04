<?php

namespace App\Http\Controllers;

use App\Models\MotorBrochure;
use App\Models\MotorProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminMotorController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'family' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'archived', 'inactive'])],
            'brochure_id' => ['nullable', 'integer', 'exists:motor_brochures,id'],
        ]);

        $motors = MotorProduct::query()
            ->with(['brochureItems' => fn ($query) => $query
                ->with(['brochure:id,period_label,source_name,status', 'financingOptions'])
                ->whereHas('brochure', fn (Builder $brochure) => $brochure->where('status', 'active'))])
            ->withCount('creditApplications')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(
                fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
            ))
            ->when($filters['family'] ?? null, fn (Builder $query, string $family) => $query->where('family', $family))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['brochure_id'] ?? null, fn (Builder $query, int $brochureId) => $query->whereHas(
                'brochureItems',
                fn (Builder $items) => $items->where('brochure_id', $brochureId)
            ))
            ->orderBy('family')->orderBy('name')
            ->paginate(25)
            ->withQueryString();
        $motors->getCollection()->transform(function (MotorProduct $motor): array {
            $activeItem = $motor->brochureItems->sortByDesc('id')->first();

            return [
                'id' => $motor->id,
                'code' => $motor->code,
                'name' => $motor->name,
                'family' => $motor->family,
                'status' => $motor->status,
                'cashPrice' => $activeItem?->cash_price,
                'brochurePeriod' => $activeItem?->brochure?->period_label,
                'brochureStatus' => $activeItem?->brochure?->status,
                'financingOptionCount' => $activeItem?->financingOptions->count() ?? 0,
                'applicationCount' => $motor->credit_applications_count,
            ];
        });

        return Inertia::render('admin/motors/IndexPage', [
            'motors' => $motors,
            'families' => MotorProduct::query()->distinct()->orderBy('family')->pluck('family'),
            'brochures' => MotorBrochure::query()->withCount(['items', 'financingOptions'])
                ->orderByDesc('id')->get(['id', 'period_label', 'source_name', 'status'])
                ->map(fn (MotorBrochure $brochure) => [
                    'id' => $brochure->id,
                    'period_label' => $brochure->period_label,
                    'source_name' => $brochure->source_name,
                    'status' => $brochure->status,
                    'items_count' => (int) $brochure->items_count,
                    'financing_options_count' => (int) $brochure->financing_options_count,
                ])->values(),
            'filters' => $filters,
        ]);
    }
}
