<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Helpers\ApiResponse;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            $requestData = $request->all();
            
            // Remove sensitive information
            foreach ($this->dontFlash as $key) {
                unset($requestData[$key]);
            }

            Log::error('404 Not Found: ' . $request->url(), [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $requestData
            ]);

            // Return API response for API routes
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'RESOURCE_NOT_FOUND',
                    'The requested resource was not found',
                    [],
                    404
                );
            }
        });

        // Handle authentication errors for API routes
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'UNAUTHORIZED',
                    'Authentication required',
                    [],
                    401
                );
            }
        });

        // Handle validation errors for API routes
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'VALIDATION_ERROR',
                    'The given data was invalid',
                    $e->errors(),
                    422
                );
            }
        });

        // Handle model not found errors for API routes
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return ApiResponse::error(
                    'RESOURCE_NOT_FOUND',
                    'The requested resource was not found',
                    [],
                    404
                );
            }
        });

        // Handle rate limit errors for API routes
        $this->renderable(function (HttpException $e, $request) {
            if ($request->is('api/*') && $e->getStatusCode() === 429) {
                return ApiResponse::error(
                    'RATE_LIMIT_EXCEEDED',
                    'Too many requests. Please try again later',
                    [],
                    429
                );
            }
        });

        // Handle general HTTP exceptions for API routes
        $this->renderable(function (HttpException $e, $request) {
            if ($request->is('api/*')) {
                $statusCode = $e->getStatusCode();
                
                $errorCodes = [
                    400 => 'BAD_REQUEST',
                    401 => 'UNAUTHORIZED',
                    403 => 'FORBIDDEN',
                    404 => 'RESOURCE_NOT_FOUND',
                    405 => 'METHOD_NOT_ALLOWED',
                    500 => 'INTERNAL_ERROR',
                ];

                $code = $errorCodes[$statusCode] ?? 'HTTP_ERROR';
                $message = $e->getMessage() ?: 'An error occurred';

                return ApiResponse::error($code, $message, [], $statusCode);
            }
        });

        // Handle all other exceptions for API routes
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') && !config('app.debug')) {
                Log::error('API Error: ' . $e->getMessage(), [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return ApiResponse::error(
                    'INTERNAL_ERROR',
                    'An internal error occurred',
                    [],
                    500
                );
            }
        });
    }
}