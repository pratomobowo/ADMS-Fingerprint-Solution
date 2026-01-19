<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebhookConfigRequest;
use App\Models\WebhookConfig;
use App\Services\WebhookService;
use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class WebhookConfigController extends Controller
{
    public function __construct(
        private WebhookService $webhookService
    ) {}

    /**
     * @OA\Get(
     *     path="/admin/webhooks",
     *     summary="List all webhook configurations",
     *     description="Retrieve a list of all webhook configurations (admin only)",
     *     operationId="listWebhooks",
     *     tags={"Webhook Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/WebhookConfig")
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
        $webhooks = WebhookConfig::orderBy('created_at', 'desc')->get();

        return ApiResponse::success($webhooks);
    }

    /**
     * @OA\Post(
     *     path="/admin/webhooks",
     *     summary="Create a new webhook configuration",
     *     description="Configure a new webhook endpoint to receive attendance data",
     *     operationId="createWebhook",
     *     tags={"Webhook Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WebhookConfigRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Webhook created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/WebhookConfig")
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
    public function store(WebhookConfigRequest $request): JsonResponse
    {
        $webhookConfig = WebhookConfig::create($request->validated());

        return ApiResponse::success($webhookConfig, null, 201);
    }

    /**
     * @OA\Get(
     *     path="/admin/webhooks/{id}",
     *     summary="Get a webhook configuration",
     *     description="Retrieve a specific webhook configuration by ID",
     *     operationId="getWebhook",
     *     tags={"Webhook Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Webhook configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/WebhookConfig")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $webhookConfig = WebhookConfig::find($id);

        if (!$webhookConfig) {
            return ApiResponse::error(
                'RESOURCE_NOT_FOUND',
                'Webhook configuration not found',
                [],
                404
            );
        }

        return ApiResponse::success($webhookConfig);
    }

    /**
     * @OA\Put(
     *     path="/admin/webhooks/{id}",
     *     summary="Update a webhook configuration",
     *     description="Update an existing webhook configuration",
     *     operationId="updateWebhook",
     *     tags={"Webhook Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Webhook configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/WebhookConfigRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/WebhookConfig")
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function update(WebhookConfigRequest $request, int $id): JsonResponse
    {
        $webhookConfig = WebhookConfig::find($id);

        if (!$webhookConfig) {
            return ApiResponse::error(
                'RESOURCE_NOT_FOUND',
                'Webhook configuration not found',
                [],
                404
            );
        }

        $webhookConfig->update($request->validated());

        return ApiResponse::success($webhookConfig->fresh());
    }

    /**
     * @OA\Delete(
     *     path="/admin/webhooks/{id}",
     *     summary="Delete a webhook configuration",
     *     description="Remove a webhook configuration",
     *     operationId="deleteWebhook",
     *     tags={"Webhook Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Webhook configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Webhook configuration deleted successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $webhookConfig = WebhookConfig::find($id);

        if (!$webhookConfig) {
            return ApiResponse::error(
                'RESOURCE_NOT_FOUND',
                'Webhook configuration not found',
                [],
                404
            );
        }

        $webhookConfig->delete();

        return ApiResponse::success(['message' => 'Webhook configuration deleted successfully']);
    }

    /**
     * @OA\Post(
     *     path="/admin/webhooks/{id}/test",
     *     summary="Test a webhook configuration",
     *     description="Send a test payload to verify webhook configuration",
     *     operationId="testWebhook",
     *     tags={"Webhook Management"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Webhook configuration ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test result",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string", example="success"),
     *                 @OA\Property(property="http_status_code", type="integer", example=200),
     *                 @OA\Property(property="response_body", type="string", example="OK")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Webhook not found",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function test(int $id): JsonResponse
    {
        $webhookConfig = WebhookConfig::find($id);

        if (!$webhookConfig) {
            return ApiResponse::error(
                'RESOURCE_NOT_FOUND',
                'Webhook configuration not found',
                [],
                404
            );
        }

        $result = $this->webhookService->testWebhook($webhookConfig);

        return ApiResponse::success($result);
    }
}
