<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class CustomersExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'customers';
    }
}
