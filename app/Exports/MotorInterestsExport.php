<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class MotorInterestsExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'motor_interest';
    }
}
