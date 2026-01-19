<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\WebhookConfig;
use App\Models\WebhookDeliveryLog;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendAttendanceWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Attendance $attendance,
        public WebhookConfig $webhookConfig
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WebhookService $webhookService): void
    {
        // Send attendance to webhook with attempt number
        // WebhookService will handle logging
        $webhookService->sendAttendance(
            $this->attendance, 
            $this->webhookConfig,
            $this->attempts()
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        // Log final failed delivery after all retries exhausted
        WebhookDeliveryLog::create([
            'webhook_config_id' => $this->webhookConfig->id,
            'attendance_id' => $this->attendance->id,
            'status' => 'failed',
            'error_message' => 'All retry attempts exhausted: ' . $exception->getMessage(),
            'attempt_number' => $this->attempts(),
            'delivered_at' => null,
        ]);
    }
}
