<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class DocumentsExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'documents';
    }
}
