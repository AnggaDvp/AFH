<?php

namespace App\Exports\Admin;

use App\Services\AdminReportService;
use Illuminate\Database\Query\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

abstract class AdminReportExport implements FromQuery, WithHeadings, WithMapping, WithCustomChunkSize, WithColumnFormatting
{
    protected array $filters;

    abstract protected function reportType(): string;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        return app(AdminReportService::class)->query($this->reportType(), $this->filters);
    }

    public function headings(): array
    {
        return array_values(app(AdminReportService::class)->columns($this->reportType()));
    }

    public function map($row): array
    {
        $columns = app(AdminReportService::class)->columns($this->reportType());

        return array_map(function (string $key) use ($row) {
            $value = $row->{$key} ?? null;
            if (is_string($value) && preg_match('/^[=+\-@\t\r]/', $value)) {
                return "'" . $value;
            }

            return $value;
        }, array_keys($columns));
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function columnFormats(): array
    {
        $columns = array_keys(app(AdminReportService::class)->columns($this->reportType()));
        $formats = [];
        foreach ($columns as $index => $key) {
            if (in_array($key, ['phone', 'postal_code', 'application_number'], true)) {
                $column = '';
                $number = $index + 1;
                while ($number > 0) {
                    $remainder = ($number - 1) % 26;
                    $column = chr(65 + $remainder) . $column;
                    $number = intdiv($number - 1, 26);
                }
                $formats[$column] = NumberFormat::FORMAT_TEXT;
            }
        }

        return $formats;
    }
}
