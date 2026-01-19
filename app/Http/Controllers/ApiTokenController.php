<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiTokenRequest;
use App\Models\ApiToken;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Annotations as OA;

class ApiTokenController extends Controller
{
    /**
     * @OA\Get(
     *     path="/admin/tokens",
     *     summary="List all API tokens",
     *     description="Retrieve a list of all API tokens (admin only)",
     *     operationId="listApiTokens",
     *     tags={"API Token Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ApiToken")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $tokens = ApiToken::orderBy('created_at', 'desc')->get();

        return ApiResponse::success($tokens);
    }

    /**
     * @OA\Post(
     *     path="/admin/tokens",
     *     summary="Generate a new API token",
     *     description="Create a new API token for authentication. The token is only visible in this response.",
     *     operationId="createApiToken",
     *     tags={"API Token Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 description="Token name/description",
     *                 example="HR Application Token"
     *             ),
     *             @OA\Property(
     *                 property="expires_at",
     *                 type="string",
     *                 format="date-time",
     *                 description="Optional expiration date",
     *                 example="2026-11-15T00:00:00Z"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Token created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/ApiTokenWithToken"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid request",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(ApiTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Generate a secure random token
        $token = Str::random(64);

        // Create the API token
        $apiToken = ApiToken::create([
            'name' => $validated['name'],
            'token' => $token,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
        ]);

        // Return the token in the response (only time it's visible)
        $apiToken->makeVisible('token');

        return ApiResponse::success($apiToken, null, 201);
    }

    /**
     * @OA\Put(
     *     path="/admin/tokens/{id}/revoke",
     *     summary="Revoke an API token",
     *     description="Deactivate an API token to prevent further use",
     *     operationId="revokeApiToken",
     *     tags={"API Token Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="API Token ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token revoked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ApiToken")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Token not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function revoke(int $id): JsonResponse
    {
        $apiToken = ApiToken::find($id);

        if (!$apiToken) {
            return ApiResponse::error(
                'RESOURCE_NOT_FOUND',
                'API token not found',
                [],
                404
            );
        }

        $apiToken->update(['is_active' => false]);

        return ApiResponse::success($apiToken->fresh());
    }
}
