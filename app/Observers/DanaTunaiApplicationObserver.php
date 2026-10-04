<?php

namespace App\Observers;

use App\Models\DanaTunaiApplication;
use App\Services\AdminNotificationService;

class DanaTunaiApplicationObserver
{
    public function created(DanaTunaiApplication $application): void
    {
        $application->loadMissing('customer:id,full_name');
        app(AdminNotificationService::class)->notifyActiveAdmins(
            'new_application',
            'Pengajuan Dana Tunai baru',
            'Pengajuan ' . $application->application_number . ' dari ' . $application->customer->full_name . ' telah dibuat.',
            [
                'application_id' => $application->id,
                'url' => '/admin/dana-tunai/' . $application->id,
            ]
        );
    }
}
