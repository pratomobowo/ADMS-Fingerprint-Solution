<?php

namespace App\Http\Controllers\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="WebhookConfig",
 *     title="Webhook Configuration",
 *     description="Webhook configuration model",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Webhook configuration ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Webhook name/description",
 *         example="HR System Webhook"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="uri",
 *         description="Webhook URL (must be HTTPS)",
 *         example="https://hr.example.com/api/webhooks/attendance"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether the webhook is active",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="secret_key",
 *         type="string",
 *         description="Secret key for signature verification",
 *         example="your-secret-key-here"
 *     ),
 *     @OA\Property(
 *         property="headers",
 *         type="object",
 *         description="Additional HTTP headers to send",
 *         example={"X-Custom-Header": "value"},
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="retry_attempts",
 *         type="integer",
 *         description="Number of retry attempts on failure",
 *         example=3
 *     ),
 *     @OA\Property(
 *         property="timeout",
 *         type="integer",
 *         description="Request timeout in seconds",
 *         example=30
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Configuration creation timestamp",
 *         example="2025-11-15T08:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Configuration last update timestamp",
 *         example="2025-11-15T08:00:00Z"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="WebhookConfigRequest",
 *     title="Webhook Configuration Request",
 *     description="Request body for creating/updating webhook configuration",
 *     required={"name", "url", "secret_key"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Webhook name/description",
 *         example="HR System Webhook"
 *     ),
 *     @OA\Property(
 *         property="url",
 *         type="string",
 *         format="uri",
 *         description="Webhook URL (must be HTTPS)",
 *         example="https://hr.example.com/api/webhooks/attendance"
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether the webhook is active",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="secret_key",
 *         type="string",
 *         description="Secret key for signature verification",
 *         example="your-secret-key-here"
 *     ),
 *     @OA\Property(
 *         property="headers",
 *         type="object",
 *         description="Additional HTTP headers to send",
 *         example={"X-Custom-Header": "value"},
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="retry_attempts",
 *         type="integer",
 *         description="Number of retry attempts on failure (default: 3)",
 *         example=3
 *     ),
 *     @OA\Property(
 *         property="timeout",
 *         type="integer",
 *         description="Request timeout in seconds (default: 30)",
 *         example=30
 *     )
 * )
 */
class WebhookSchema
{
}
