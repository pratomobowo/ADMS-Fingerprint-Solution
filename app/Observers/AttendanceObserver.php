<?php

namespace App\Observers;

use App\Jobs\SendAttendanceWebhook;
use App\Models\Attendance;
use App\Models\WebhookConfig;

class AttendanceObserver
{
    /**
     * Handle the Attendance "created" event.
     */
    public function created(Attendance $attendance): void
    {
        // Get all active webhook configurations
        $activeWebhooks = WebhookConfig::where('is_active', true)->get();

        // Dispatch webhook job for each active webhook
        foreach ($activeWebhooks as $webhookConfig) {
            SendAttendanceWebhook::dispatch($attendance, $webhookConfig);
        }
    }
}
