<?php

namespace App\Services;

use App\Models\ApiRequestLog;
use App\Models\WebhookDeliveryLog;
use App\Models\WebhookConfig;
use App\Models\Attendance;
use Illuminate\Http\Request;

class LoggingService
{
    /**
     * Log API request
     *
     * @param Request $request
     * @param int|null $apiTokenId
     * @param int $statusCode
     * @param float $responseTime Response time in milliseconds
     * @return ApiRequestLog
     */
    public function logApiRequest(
        Request $request,
        ?int $apiTokenId,
        int $statusCode,
        float $responseTime
    ): ApiRequestLog {
        return ApiRequestLog::create([
            'api_token_id' => $apiTokenId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'query_params' => $request->query(),
            'status_code' => $statusCode,
            'response_time' => (int) $responseTime,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Log webhook delivery
     *
     * @param int $webhookConfigId
     * @param int $attendanceId
     * @param string $status Status: 'pending', 'success', 'failed'
     * @param int|null $httpStatusCode
     * @param string|null $responseBody
     * @param string|null $errorMessage
     * @param int $attemptNumber
     * @return WebhookDeliveryLog
     */
    public function logWebhookDelivery(
        int $webhookConfigId,
        int $attendanceId,
        string $status,
        ?int $httpStatusCode = null,
        ?string $responseBody = null,
        ?string $errorMessage = null,
        int $attemptNumber = 1
    ): WebhookDeliveryLog {
        $data = [
            'webhook_config_id' => $webhookConfigId,
            'attendance_id' => $attendanceId,
            'status' => $status,
            'http_status_code' => $httpStatusCode,
            'response_body' => $responseBody,
            'error_message' => $errorMessage,
            'attempt_number' => $attemptNumber,
        ];

        // Set delivered_at timestamp for successful deliveries
        if ($status === 'success') {
            $data['delivered_at'] = now();
        }

        return WebhookDeliveryLog::create($data);
    }
}
