<?php

namespace App\Http\Controllers;

use App\Models\MotorBrochure;
use App\Models\MotorBrochureItem;
use App\Models\MotorProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerAreaController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->renderCustomerPage($request, 'customer/CustomerHome');
    }

    public function motor(Request $request): Response
    {
        $brochure = MotorBrochure::query()
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
        $search = trim((string) $request->query('search', ''));
        $family = trim((string) $request->query('family', ''));
        $items = collect();
        $families = collect();

        if ($brochure) {
            $items = MotorBrochureItem::query()
                ->with('motorProduct:id,code,name,family')
                ->where('brochure_id', $brochure->id)
                ->whereHas('motorProduct', function (Builder $query) use ($search, $family): void {
                    $query->where('status', 'active')
                        ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%' . $search . '%'))
                        ->when($family !== '', fn (Builder $query) => $query->where('family', $family));
                })
                ->orderBy('id')
                ->get(['id', 'motor_product_id', 'cash_price'])
                ->map(fn (MotorBrochureItem $item): array => [
                    'code' => $item->motorProduct->code,
                    'name' => $item->motorProduct->name,
                    'family' => $item->motorProduct->family,
                    'cashPrice' => $item->cash_price,
                    'detailUrl' => route('customer.motor.show', ['motor' => $item->motorProduct->code]),
                ]);

            $families = MotorProduct::query()
                ->where('status', 'active')
                ->whereHas('brochureItems', fn (Builder $query) => $query->where('brochure_id', $brochure->id))
                ->distinct()
                ->orderBy('family')
                ->pluck('family')
                ->values();
        }

        $customer = $request->attributes->get('customer');

        return Inertia::render('customer/MotorPage', [
            'customerName' => $customer->full_name,
            'customerPhone' => $customer->phone,
            'brochure' => $brochure ? [
                'periodLabel' => $brochure->period_label,
                'sourceName' => $brochure->source_name,
            ] : null,
            'search' => $search,
            'selectedFamily' => $family,
            'families' => $families,
            'motors' => $items,
        ]);
    }

    public function motorDetail(Request $request, MotorProduct $motor): Response
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
        $customer = $request->attributes->get('customer');

        return Inertia::render('customer/MotorDetailPage', [
            'customerName' => $customer->full_name,
            'customerPhone' => $customer->phone,
            'motor' => [
                'id' => $motor->id,
                'code' => $motor->code,
                'name' => $motor->name,
                'family' => $motor->family,
                'cashPrice' => $item->cash_price,
            ],
            'brochure' => [
                'periodLabel' => $item->brochure->period_label,
                'sourceName' => $item->brochure->source_name,
                'notes' => $item->brochure->notes,
            ],
            'financingOptions' => $item->financingOptions->map(fn ($option): array => [
                'downPayment' => $option->down_payment_source_value,
                'tenorCode' => $option->tenor_code,
                'tenorLabel' => $option->tenor_label,
                'installmentValue' => $option->installment_source_value,
                'isAvailable' => $option->is_available,
            ])->values(),
        ]);
    }

    public function danaTunai(Request $request): Response
    {
        $customer = $request->attributes->get('customer');

        return Inertia::render('customer/DanaTunaiPage', [
            'customerName' => $customer->full_name,
            'customerPhone' => $customer->phone,
            'submissionKey' => (string) Str::uuid(),
            'submissionError' => $request->session()->get('cash_loan_error'),
        ]);
    }

    public function simulasi(Request $request): Response
    {
        return $this->renderCustomerPage($request, 'customer/SimulationPage');
    }

    public function tentang(Request $request): Response
    {
        return $this->renderCustomerPage($request, 'customer/AboutPage');
    }

    private function renderCustomerPage(Request $request, string $component): Response
    {
        $customer = $request->attributes->get('customer');

        return Inertia::render($component, [
            'customerName' => $customer->full_name,
            'customerPhone' => $customer->phone,
        ]);
    }
}
