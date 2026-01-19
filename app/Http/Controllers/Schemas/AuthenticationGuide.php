<?php

namespace App\Http\Controllers\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="API authentication using Bearer tokens"
 * )
 * 
 * Authentication Guide:
 * 
 * The ADMS HR API uses Bearer token authentication. To access protected endpoints:
 * 
 * 1. Obtain an API token from the administrator
 * 2. Include the token in the Authorization header of your requests:
 *    Authorization: Bearer {your-token-here}
 * 
 * Example:
 * ```
 * curl -X GET "https://api.example.com/api/v1/hr/attendances" \
 *      -H "Authorization: Bearer abcdef1234567890..."
 * ```
 * 
 * Token Management:
 * - Tokens can be created via POST /api/v1/admin/tokens
 * - Tokens can be revoked via PUT /api/v1/admin/tokens/{id}/revoke
 * - Tokens may have an optional expiration date
 * - Inactive or expired tokens will result in 401 Unauthorized responses
 * 
 * Rate Limiting:
 * - API requests are rate-limited to 60 requests per minute per token
 * - Exceeding the rate limit will result in 429 Too Many Requests responses
 * 
 * Security Best Practices:
 * - Store tokens securely and never expose them in client-side code
 * - Use HTTPS for all API requests
 * - Rotate tokens periodically
 * - Revoke tokens immediately if compromised
 */
class AuthenticationGuide
{
}
