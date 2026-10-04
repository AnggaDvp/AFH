<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class CustomerGrowthExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'customer_growth';
    }
}
