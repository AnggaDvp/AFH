<?php

namespace App\Http\Controllers;

use App\Exports\ApplicationStatusExport;
use App\Exports\ChatActivityExport;
use App\Exports\CreditApplicationsExport;
use App\Exports\CustomerGrowthExport;
use App\Exports\CustomersExport;
use App\Exports\DanaTunaiExport;
use App\Exports\DocumentsExport;
use App\Exports\MotorInterestsExport;
use App\Exports\MotorInterestSummaryExport;
use App\Models\MotorProduct;
use App\Models\Region;
use App\Models\ReportExport;
use App\Services\AdminActivityLogger;
use App\Services\AdminReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminReportController extends Controller
{
    private const EXPORTS = [
        'customers' => CustomersExport::class,
        'motor_interest' => MotorInterestsExport::class,
        'credit_applications' => CreditApplicationsExport::class,
        'dana_tunai' => DanaTunaiExport::class,
        'chat_activity' => ChatActivityExport::class,
        'documents' => DocumentsExport::class,
        'customer_growth' => CustomerGrowthExport::class,
        'application_status' => ApplicationStatusExport::class,
        'motor_interest_summary' => MotorInterestSummaryExport::class,
    ];

    public function index(Request $request, AdminReportService $reports): Response
    {
        $filters = $this->validatedFilters($request, $reports);
        $type = $filters['report_type'] ?? 'customers';
        unset($filters['report_type']);
        $filters = $this->normalizeFilters($filters);

        $results = $reports->query($type, $filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($row): array => (array) $row);

        return Inertia::render('admin/reports/IndexPage', [
            'adminName' => $request->user()->name,
            'adminRole' => $request->user()->adminRole(),
            'reportTypes' => $reports->reportTypes(),
            'columns' => $reports->columns($type),
            'results' => $results,
            'filters' => ['report_type' => $type] + $filters,
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'motors' => MotorProduct::query()->orderBy('name')->get(['id', 'name']),
            'periodLabel' => $this->periodLabel($filters),
        ]);
    }

    public function export(
        Request $request,
        AdminReportService $reports,
        AdminActivityLogger $activityLogger
    ): BinaryFileResponse {
        $filters = $this->validatedFilters($request, $reports);
        $type = $filters['report_type'] ?? 'customers';
        unset($filters['report_type']);
        $filters = $this->normalizeFilters($filters);

        $reportQuery = $reports->query($type, $filters);
        $rowCount = (clone $reportQuery)->count();
        $filename = $this->filename($type, $filters);

        $exportRecord = ReportExport::create([
            'admin_id' => $request->user()->id,
            'report_type' => $type,
            'filters' => $filters ?: null,
            'file_name' => $filename,
            'row_count' => $rowCount,
            'created_at' => now(),
        ]);
        $activityLogger->record(
            $request->user(),
            'report.exported',
            'report_export',
            $exportRecord->id,
            ['report_type' => $type, 'row_count' => $rowCount, 'file_name' => $filename]
        );

        $exportClass = self::EXPORTS[$type];

        return Excel::download(new $exportClass($filters), $filename);
    }

    private function validatedFilters(Request $request, AdminReportService $reports): array
    {
        $filters = $request->validate([
            'report_type' => ['nullable', 'string', Rule::in(array_keys($reports->reportTypes()))],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'motor_id' => ['nullable', 'integer', 'exists:motor_products,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        if (!empty($filters['from']) && !empty($filters['to']) && $filters['from'] > $filters['to']) {
            throw ValidationException::withMessages([
                'to' => 'Tanggal akhir harus sama atau setelah tanggal awal.',
            ]);
        }

        return $filters;
    }

    private function normalizeFilters(array $filters): array
    {
        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    private function periodLabel(array $filters): string
    {
        if (empty($filters['from']) && empty($filters['to'])) {
            return 'Semua tanggal';
        }

        return ($filters['from'] ?? 'Awal data') . ' – ' . ($filters['to'] ?? 'Hari ini');
    }

    private function filename(string $type, array $filters): string
    {
        $reportName = Str::studly($type);
        $from = $filters['from'] ?? 'all';
        $to = $filters['to'] ?? 'all';

        return "AFH_{$reportName}_Report_{$from}_{$to}.xlsx";
    }
}
