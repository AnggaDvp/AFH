<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class DanaTunaiExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'dana_tunai';
    }
}
