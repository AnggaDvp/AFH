<?php

namespace App\Exports;

use App\Exports\Admin\AdminReportExport;

class ChatActivityExport extends AdminReportExport
{
    protected function reportType(): string
    {
        return 'chat_activity';
    }
}
