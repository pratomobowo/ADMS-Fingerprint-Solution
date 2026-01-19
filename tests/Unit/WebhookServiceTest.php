<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\WebhookConfig;
use App\Services\LoggingService;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    private WebhookService $service;
    private LoggingService $loggingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loggingService = $this->createMock(LoggingService::class);
        $this->service = new WebhookService($this->loggingService);
    }

    /** @test */
    public function it_sends_attendance_to_webhook_successfully()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
            'timestamp' => '2025-11-15 08:30:00',
            'device_sn' => 'BWNF184660256',
            'status1' => true,
            'status2' => false,
            'status3' => false,
            'status4' => false,
            'status5' => false,
        ]);

        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key',
            'timeout' => 30,
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        // Mock logging service
        $this->loggingService->expects($this->once())
            ->method('logWebhookDelivery')
            ->with(
                $webhookConfig->id,
                $attendance->id,
                'success',
                200,
                $this->anything(),
                null,
                1
            );

        // Send webhook
        $result = $this->service->sendAttendance($attendance, $webhookConfig);

        $this->assertTrue($result);

        // Verify HTTP request was made
        Http::assertSent(function ($request) use ($webhookConfig) {
            return $request->url() === $webhookConfig->url &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request->hasHeader('X-Webhook-Signature');
        });
    }

    /** @test */
    public function it_generates_correct_signature()
    {
        $payload = [
            'event' => 'attendance.created',
            'timestamp' => '2025-11-15T08:30:00+00:00',
            'data' => [
                'id' => 1,
                'employee_id' => 191,
            ],
        ];

        $secret = 'test-secret-key';

        // Generate signature
        $signature = $this->service->generateSignature($payload, $secret);

        // Verify signature format
        $this->assertIsString($signature);
        $this->assertEquals(64, strlen($signature)); // SHA256 produces 64 character hex string

        // Verify signature is consistent
        $signature2 = $this->service->generateSignature($payload, $secret);
        $this->assertEquals($signature, $signature2);

        // Verify different secret produces different signature
        $signature3 = $this->service->generateSignature($payload, 'different-secret');
        $this->assertNotEquals($signature, $signature3);
    }

    /** @test */
    public function it_handles_webhook_failure()
    {
        // Create test data
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key',
        ]);

        // Mock HTTP failure response
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        // Mock logging service - expect it to be called twice (once for failed response, once in catch block)
        $this->loggingService->expects($this->atLeastOnce())
            ->method('logWebhookDelivery');

        // Expect exception
        $this->expectException(\Exception::class);

        // Send webhook
        $this->service->sendAttendance($attendance, $webhookConfig);
    }

    /** @test */
    public function it_tests_webhook_configuration_successfully()
    {
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key',
            'timeout' => 30,
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'ok'], 200),
        ]);

        // Test webhook
        $result = $this->service->testWebhook($webhookConfig);

        // Assert result structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('status_code', $result);
        $this->assertArrayHasKey('response_body', $result);

        // Assert values
        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['status_code']);

        // Verify HTTP request was made
        Http::assertSent(function ($request) use ($webhookConfig) {
            $body = json_decode($request->body(), true);
            return $request->url() === $webhookConfig->url &&
                   $body['event'] === 'webhook.test' &&
                   isset($body['signature']);
        });
    }

    /** @test */
    public function it_handles_test_webhook_failure()
    {
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key',
        ]);

        // Mock HTTP failure
        Http::fake([
            'example.com/*' => Http::response(['error' => 'Not Found'], 404),
        ]);

        // Test webhook
        $result = $this->service->testWebhook($webhookConfig);

        // Assert failure result
        $this->assertFalse($result['success']);
        $this->assertEquals(404, $result['status_code']);
    }

    /** @test */
    public function it_includes_custom_headers_in_webhook_request()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => 191,
        ]);
        $webhookConfig = WebhookConfig::factory()->create([
            'url' => 'https://example.com/webhook',
            'secret_key' => 'test-secret-key',
            'headers' => [
                'X-Custom-Header' => 'custom-value',
                'X-API-Key' => 'api-key-123',
            ],
        ]);

        // Mock HTTP response
        Http::fake([
            'example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        $this->loggingService->method('logWebhookDelivery');

        // Send webhook
        $this->service->sendAttendance($attendance, $webhookConfig);

        // Verify custom headers were included
        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Custom-Header', 'custom-value') &&
                   $request->hasHeader('X-API-Key', 'api-key-123');
        });
    }
}
