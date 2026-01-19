<?php

namespace Tests\Feature;

use App\Jobs\SendAttendanceWebhook;
use App\Models\Attendance;
use App\Models\WebhookConfig;
use App\Models\WebhookDeliveryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookDeliveryTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_dispatches_webhook_job_when_attendance_created()
    {
        Queue::fake();

        // Create active webhook config
        $webhookConfig = WebhookConfig::factory()->create([
            'is_active' => true,
        ]);

        // Create attendance (should trigger observer)
        $attendance = Attendance::factory()->create();

        // Assert job was dispatched
        Queue::assertPushed(SendAttendanceWebhook::class, function ($job) use ($attendance, $webhookConfig) {
            return $job->attendance->id === $attendance->id &&
                   $job->webhookConfig->id === $webhookConfig->id;
        });
    }

    /** @test */
    public function it_does_not_dispatch_webhook_for_inactive_config()
    {
        Queue::fake();

        // Create inactive webhook config
        WebhookConfig::factory()->create([
            'is_active' => false,
        ]);

        // Create attendance
        Attendance::factory()->create();

        // Assert job was not dispatched
        Queue::assertNotPushed(SendAttendanceWebhook::class);
    }

    /** @test */
    public function it_dispatches_multiple_webhooks_for_multiple_configs()
    {
        Queue::fake();

        // Create multiple active webhook configs
        $webhook1 = WebhookConfig::factory()->create(['is_active' => true]);
        $webhook2 = WebhookConfig::factory()->create(['is_active' => true]);

        // Create attendance
        $attendance = Attendance::factory()->create();

        // Assert job was dispatched twice
        Queue::assertPushed(SendAttendanceWebhook::class, 2);
    }

    /** @test */
    public function it_delivers_webhook_successfully()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret',
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        // Execute job
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);
        $job->handle(app(\App\Services\WebhookService::class));

        // Assert delivery log was created
        $this->assertDatabaseHas('webhook_delivery_logs', [
            'webhook_config_id' => $webhookConfig->id,
            'attendance_id' => $attendance->id,
            'status' => 'success',
            'http_status_code' => 200,
        ]);

        // Verify HTTP request was made
        Http::assertSent(function ($request) use ($webhookConfig) {
            return $request->url() === $webhookConfig->url;
        });
    }

    /** @test */
    public function it_logs_failed_webhook_delivery()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret',
        ]);

        // Mock HTTP failure
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        // Execute job and expect it to fail
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);
        
        try {
            $job->handle(app(\App\Services\WebhookService::class));
        } catch (\Exception $e) {
            // Expected to throw exception
        }

        // Assert delivery log was created with failed status
        $this->assertDatabaseHas('webhook_delivery_logs', [
            'webhook_config_id' => $webhookConfig->id,
            'attendance_id' => $attendance->id,
            'status' => 'failed',
            'http_status_code' => 500,
        ]);
    }

    /** @test */
    public function it_retries_failed_webhook_delivery()
    {
        Queue::fake();

        // Create test data
        $attendance = Attendance::factory()->create();
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);

        // Dispatch job
        SendAttendanceWebhook::dispatch($attendance, $webhookConfig);

        // Assert job was pushed
        Queue::assertPushed(SendAttendanceWebhook::class);

        // Verify job has retry configuration
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals([60, 300, 900], $job->backoff);
    }

    /** @test */
    public function it_logs_failed_job_after_max_retries()
    {
        // Create test data
        $attendance = Attendance::factory()->create();
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);

        // Mock HTTP failure
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Service Unavailable'], 503),
        ]);

        // Create job
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);

        // Simulate failed method being called
        $exception = new \Exception('Webhook delivery failed with status code: 503');
        $job->failed($exception);

        // Assert delivery log exists with failed status
        $this->assertDatabaseHas('webhook_delivery_logs', [
            'webhook_config_id' => $webhookConfig->id,
            'attendance_id' => $attendance->id,
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_includes_signature_in_webhook_payload()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key',
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        // Execute job
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);
        $job->handle(app(\App\Services\WebhookService::class));

        // Verify signature was included in request
        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);
            return isset($body['signature']) &&
                   $request->hasHeader('X-Webhook-Signature');
        });
    }

    /** @test */
    public function it_logs_attempt_number_for_retries()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        // Execute job with attempt number
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);
        $job->handle(app(\App\Services\WebhookService::class));

        // Assert delivery log has attempt number
        $log = WebhookDeliveryLog::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals(1, $log->attempt_number);
    }

    /** @test */
    public function it_stores_response_body_in_delivery_log()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
        ]);

        $responseBody = ['status' => 'received', 'id' => '12345'];

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response($responseBody, 200),
        ]);

        // Execute job
        $job = new SendAttendanceWebhook($attendance, $webhookConfig);
        $job->handle(app(\App\Services\WebhookService::class));

        // Assert response body was stored
        $log = WebhookDeliveryLog::where('attendance_id', $attendance->id)->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('received', $log->response_body);
    }
}
