<?php

namespace App\Http\Controllers\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Webhook payload format and signature verification"
 * )
 * 
 * @OA\Schema(
 *     schema="WebhookPayload",
 *     title="Webhook Payload",
 *     description="Payload sent to webhook endpoints when attendance is created",
 *     @OA\Property(
 *         property="event",
 *         type="string",
 *         description="Event type",
 *         example="attendance.created"
 *     ),
 *     @OA\Property(
 *         property="timestamp",
 *         type="string",
 *         format="date-time",
 *         description="Event timestamp",
 *         example="2025-11-15T08:30:05Z"
 *     ),
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/Attendance",
 *         description="Attendance data"
 *     ),
 *     @OA\Property(
 *         property="signature",
 *         type="string",
 *         description="HMAC SHA256 signature for verification",
 *         example="a1b2c3d4e5f6..."
 *     )
 * )
 * 
 * Webhook Integration Guide:
 * 
 * ## Overview
 * When a new attendance record is created in ADMS, the system automatically sends
 * a webhook notification to all active webhook endpoints configured in the system.
 * 
 * ## Payload Format
 * The webhook payload is sent as a JSON POST request with the following structure:
 * 
 * ```json
 * {
 *   "event": "attendance.created",
 *   "timestamp": "2025-11-15T08:30:05Z",
 *   "data": {
 *     "id": 1,
 *     "employee_id": "12345",
 *     "timestamp": "2025-11-15T08:30:00Z",
 *     "device_sn": "BWN001",
 *     "status": {
 *       "status1": true,
 *       "status2": false,
 *       "status3": false,
 *       "status4": false,
 *       "status5": false
 *     },
 *     "created_at": "2025-11-15T08:30:05Z"
 *   },
 *   "signature": "a1b2c3d4e5f6..."
 * }
 * ```
 * 
 * ## Signature Verification
 * Each webhook payload includes a signature for verification. To verify the signature:
 * 
 * 1. Extract the signature from the payload
 * 2. Remove the signature field from the payload
 * 3. Convert the remaining payload to JSON string
 * 4. Calculate HMAC SHA256 hash using your secret key
 * 5. Compare the calculated hash with the received signature
 * 
 * ### PHP Example:
 * ```php
 * $payload = json_decode($request->getContent(), true);
 * $receivedSignature = $payload['signature'];
 * unset($payload['signature']);
 * 
 * $calculatedSignature = hash_hmac(
 *     'sha256',
 *     json_encode($payload),
 *     'your-secret-key'
 * );
 * 
 * if (hash_equals($calculatedSignature, $receivedSignature)) {
 *     // Signature is valid
 * }
 * ```
 * 
 * ### Node.js Example:
 * ```javascript
 * const crypto = require('crypto');
 * 
 * const payload = req.body;
 * const receivedSignature = payload.signature;
 * delete payload.signature;
 * 
 * const calculatedSignature = crypto
 *   .createHmac('sha256', 'your-secret-key')
 *   .update(JSON.stringify(payload))
 *   .digest('hex');
 * 
 * if (calculatedSignature === receivedSignature) {
 *   // Signature is valid
 * }
 * ```
 * 
 * ## Retry Mechanism
 * If webhook delivery fails, ADMS will automatically retry:
 * - Attempt 1: Immediate
 * - Attempt 2: After 1 minute
 * - Attempt 3: After 5 minutes
 * - Attempt 4: After 15 minutes
 * 
 * After 3 failed attempts, the delivery is marked as failed and logged.
 * 
 * ## Response Requirements
 * Your webhook endpoint should:
 * - Respond with HTTP status 200-299 for successful processing
 * - Respond within the configured timeout (default: 30 seconds)
 * - Process the webhook asynchronously if possible
 * - Return a response quickly to avoid timeouts
 * 
 * ## Security Best Practices
 * - Always verify the signature before processing
 * - Use HTTPS for your webhook endpoint
 * - Keep your secret key secure
 * - Implement rate limiting on your endpoint
 * - Log all webhook deliveries for debugging
 * - Handle duplicate deliveries gracefully (use attendance ID for idempotency)
 * 
 * ## Testing
 * Use the POST /api/v1/admin/webhooks/{id}/test endpoint to send a test payload
 * to your webhook endpoint and verify your integration.
 */
class WebhookPayloadGuide
{
}
