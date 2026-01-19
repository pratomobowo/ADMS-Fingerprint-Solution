<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Return a success JSON response
     *
     * @param mixed $data
     * @param array|null $meta
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success($data = null, ?array $meta = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return an error JSON response
     *
     * @param string $code
     * @param string $message
     * @param array $details
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function error(string $code, string $message, array $details = [], int $statusCode = 400): JsonResponse
    {
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }
}
