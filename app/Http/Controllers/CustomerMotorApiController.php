<?php

namespace App\Http\Controllers;

use App\Models\MotorBrochure;
use App\Models\MotorBrochureItem;
use App\Models\MotorProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerMotorApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $brochure = MotorBrochure::query()
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (!$brochure) {
            return response()->json([]);
        }

        $search = trim((string) $request->query('search', ''));
        $family = trim((string) $request->query('family', ''));
        $items = MotorBrochureItem::query()
            ->with('motorProduct:id,name,family,status')
            ->where('brochure_id', $brochure->id)
            ->whereHas('motorProduct', function (Builder $query) use ($search, $family): void {
                $query->where('status', 'active')
                    ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
                    ->when($family !== '', fn (Builder $query) => $query->where('family', $family));
            })
            ->orderBy('id')
            ->get(['id', 'motor_product_id', 'cash_price'])
            ->map(fn (MotorBrochureItem $item): array => [
                'id' => $item->motorProduct->id,
                'name' => $item->motorProduct->name,
                'family' => $item->motorProduct->family,
                'cash_price' => $item->cash_price,
                'brochure_period' => $brochure->period_label,
            ])
            ->values();

        return response()->json($items);
    }

    public function show(MotorProduct $motor): JsonResponse
    {
        abort_unless($motor->status === 'active', 404);

        $item = MotorBrochureItem::query()
            ->with([
                'brochure',
                'financingOptions' => fn ($query) => $query->orderBy('id'),
            ])
            ->where('motor_product_id', $motor->id)
            ->whereHas('brochure', fn (Builder $query) => $query->where('status', 'active'))
            ->orderByDesc('id')
            ->firstOrFail();

        return response()->json([
            'product' => [
                'id' => $motor->id,
                'name' => $motor->name,
                'family' => $motor->family,
                'cash_price' => $item->cash_price,
            ],
            'brochure' => [
                'period_label' => $item->brochure->period_label,
                'source_name' => $item->brochure->source_name,
                'notes' => $item->brochure->notes,
            ],
            'financing_options' => $item->financingOptions->map(fn ($option): array => [
                'down_payment_source_value' => $option->down_payment_source_value,
                'tenor_code' => $option->tenor_code,
                'tenor_label' => $option->tenor_label,
                'installment_source_value' => $option->installment_source_value,
                'is_available' => $option->is_available,
            ])->values(),
        ]);
    }
}
