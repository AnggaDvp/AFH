<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class MotorInterestSummaryExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'motor_interest_summary';
    }
}
