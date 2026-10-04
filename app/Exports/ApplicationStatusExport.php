<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class ApplicationStatusExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'application_status';
    }
}
