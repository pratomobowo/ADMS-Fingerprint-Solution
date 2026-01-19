<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\WebhookConfig;
use App\Models\WebhookDeliveryLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class WebhookService
{
    public function __construct(
        private LoggingService $loggingService
    ) {}
    /**
     * Send attendance data to webhook URL
     *
     * @param Attendance $attendance
     * @param WebhookConfig $webhookConfig
     * @param int $attemptNumber
     * @return bool
     * @throws Exception
     */
    public function sendAttendance(Attendance $attendance, WebhookConfig $webhookConfig, int $attemptNumber = 1): bool
    {
        try {
            // Prepare payload
            $payload = [
                'event' => 'attendance.created',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'timestamp' => $attendance->timestamp->toIso8601String(),
                    'device_sn' => $attendance->device_sn ?? null,
                    'status' => [
                        'status1' => $attendance->status1,
                        'status2' => $attendance->status2,
                        'status3' => $attendance->status3,
                        'status4' => $attendance->status4,
                        'status5' => $attendance->status5,
                    ],
                ],
            ];

            // Generate signature
            $signature = $this->generateSignature($payload, $webhookConfig->secret_key);
            $payload['signature'] = $signature;

            // Prepare headers
            $headers = array_merge(
                [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                ],
                $webhookConfig->headers ?? []
            );

            // Get timeout from config or use default
            $timeout = $webhookConfig->timeout ?? config('hr_api.webhook.timeout', 30);

            // Send HTTP request
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->post($webhookConfig->url, $payload);

            // Check if request was successful
            if ($response->successful()) {
                // Log successful delivery
                $this->loggingService->logWebhookDelivery(
                    $webhookConfig->id,
                    $attendance->id,
                    'success',
                    $response->status(),
                    $response->body(),
                    null,
                    $attemptNumber
                );

                Log::info('Webhook delivered successfully', [
                    'webhook_config_id' => $webhookConfig->id,
                    'attendance_id' => $attendance->id,
                    'status_code' => $response->status(),
                    'attempt_number' => $attemptNumber,
                ]);
                
                return true;
            }

            // Log failed delivery with response details
            $errorMessage = "Webhook delivery failed with status code: {$response->status()}";
            
            $this->loggingService->logWebhookDelivery(
                $webhookConfig->id,
                $attendance->id,
                'failed',
                $response->status(),
                $response->body(),
                $errorMessage,
                $attemptNumber
            );

            Log::warning('Webhook delivery failed', [
                'webhook_config_id' => $webhookConfig->id,
                'attendance_id' => $attendance->id,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'attempt_number' => $attemptNumber,
            ]);

            throw new Exception($errorMessage);
        } catch (Exception $e) {
            // Log error details for failed deliveries
            $this->loggingService->logWebhookDelivery(
                $webhookConfig->id,
                $attendance->id,
                'failed',
                null,
                null,
                $e->getMessage(),
                $attemptNumber
            );

            Log::error('Webhook delivery error', [
                'webhook_config_id' => $webhookConfig->id,
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
                'attempt_number' => $attemptNumber,
            ]);

            throw $e;
        }
    }

    /**
     * Generate HMAC SHA256 signature for webhook payload
     *
     * @param array $payload
     * @param string $secret
     * @return string
     */
    public function generateSignature(array $payload, string $secret): string
    {
        // Remove signature field if it exists to avoid circular reference
        unset($payload['signature']);

        // Convert payload to JSON string
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Generate HMAC SHA256 hash
        return hash_hmac('sha256', $jsonPayload, $secret);
    }

    /**
     * Test webhook configuration by sending a test payload
     *
     * @param WebhookConfig $webhookConfig
     * @return array
     */
    public function testWebhook(WebhookConfig $webhookConfig): array
    {
        try {
            // Prepare test payload
            $payload = [
                'event' => 'webhook.test',
                'timestamp' => now()->toIso8601String(),
                'data' => [
                    'message' => 'This is a test webhook from ADMS',
                    'webhook_config_id' => $webhookConfig->id,
                    'webhook_name' => $webhookConfig->name,
                ],
            ];

            // Generate signature
            $signature = $this->generateSignature($payload, $webhookConfig->secret_key);
            $payload['signature'] = $signature;

            // Prepare headers
            $headers = array_merge(
                [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                ],
                $webhookConfig->headers ?? []
            );

            // Get timeout from config or use default
            $timeout = $webhookConfig->timeout ?? config('hr_api.webhook.timeout', 30);

            // Send HTTP request
            $response = Http::withHeaders($headers)
                ->timeout($timeout)
                ->post($webhookConfig->url, $payload);

            // Return test results
            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_time' => $response->handlerStats()['total_time'] ?? null,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'status_code' => null,
                'error' => $e->getMessage(),
                'response_time' => null,
            ];
        }
    }
}
