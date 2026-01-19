<?php

namespace App\Http\Controllers\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     title="Error Response",
 *     description="Standard error response format",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         description="Always false for error responses",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="error",
 *         type="object",
 *         description="Error details",
 *         @OA\Property(
 *             property="code",
 *             type="string",
 *             description="Error code",
 *             example="INVALID_TOKEN"
 *         ),
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             description="Human-readable error message",
 *             example="Token tidak valid atau expired"
 *         ),
 *         @OA\Property(
 *             property="details",
 *             type="object",
 *             description="Additional error context",
 *             example={}
 *         )
 *     )
 * )
 */
class ErrorResponseSchema
{
}
