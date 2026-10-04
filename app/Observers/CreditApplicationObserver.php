<?php

namespace App\Observers;

use App\Models\CreditApplication;
use App\Services\AdminNotificationService;
use App\Services\WhatsAppReminderService;

class CreditApplicationObserver
{
    public function created(CreditApplication $application): void
    {
        $application->loadMissing('customer:id,full_name');
        app(AdminNotificationService::class)->notifyActiveAdmins(
            'new_application',
            'Pengajuan kredit baru',
            'Pengajuan ' . $application->application_number . ' dari ' . $application->customer->full_name . ' telah dibuat.',
            [
                'application_id' => $application->id,
                'url' => '/admin/pengajuan/' . $application->id,
            ]
        );
        app(WhatsAppReminderService::class)->queueCreditApplication($application);
    }

    public function updated(CreditApplication $application): void
    {
        if (!$application->wasChanged('status')) {
            return;
        }

        $reminders = app(WhatsAppReminderService::class);
        if ($application->status === 'submitted') {
            $reminders->queueCreditApplication($application);
        } elseif ($application->status === 'need_documents') {
            $reminders->queueNeedDocuments($application);
        }
    }
}
