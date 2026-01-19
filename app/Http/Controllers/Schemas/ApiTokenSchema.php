<?php

namespace App\Http\Controllers\Schemas;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="ApiToken",
 *     title="API Token",
 *     description="API Token model (token field is hidden)",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Token ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Token name/description",
 *         example="HR Application Token"
 *     ),
 *     @OA\Property(
 *         property="last_used_at",
 *         type="string",
 *         format="date-time",
 *         description="Last time the token was used",
 *         example="2025-11-15T08:30:00Z",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         description="Token expiration date",
 *         example="2026-11-15T00:00:00Z",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Whether the token is active",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Token creation timestamp",
 *         example="2025-11-15T08:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Token last update timestamp",
 *         example="2025-11-15T08:00:00Z"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ApiTokenWithToken",
 *     title="API Token with Token Value",
 *     description="API Token model with token value (only visible on creation)",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/ApiToken"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="token",
 *                 type="string",
 *                 description="The actual token value (only visible on creation)",
 *                 example="abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890"
 *             )
 *         )
 *     }
 * )
 */
class ApiTokenSchema
{
}
