<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class CreditApplicationsExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'credit_applications';
    }
}
