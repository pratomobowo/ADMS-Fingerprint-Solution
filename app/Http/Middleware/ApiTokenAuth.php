<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiToken;
use App\Models\ApiRequestLog;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ApiTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('Token not provided');
        }

        // Validate token
        $apiToken = $this->validateToken($token);

        if (!$apiToken) {
            $this->logRequest(null, $request, 401, $startTime);
            return $this->unauthorizedResponse('Invalid or expired token');
        }

        // Check rate limiting
        if (!$this->checkRateLimit($apiToken)) {
            $this->logRequest($apiToken->id, $request, 429, $startTime);
            return $this->rateLimitResponse();
        }

        // Attach token to request for later use
        $request->attributes->set('api_token', $apiToken);

        // Process the request
        $response = $next($request);

        // Update last_used_at
        $this->updateLastUsed($apiToken);

        // Log the request
        $this->logRequest(
            $apiToken->id,
            $request,
            $response->getStatusCode(),
            $startTime
        );

        return $response;
    }

    /**
     * Extract token from Authorization header
     */
    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header) {
            return null;
        }

        // Support both "Bearer token" and "token" formats
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        return $header;
    }

    /**
     * Validate the API token
     */
    private function validateToken(string $token): ?ApiToken
    {
        $apiToken = ApiToken::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (!$apiToken) {
            return null;
        }

        // Check if token is expired
        if ($apiToken->expires_at && $apiToken->expires_at->isPast()) {
            return null;
        }

        return $apiToken;
    }

    /**
     * Check rate limiting for the token
     */
    private function checkRateLimit(ApiToken $apiToken): bool
    {
        $rateLimit = config('hr_api.rate_limit', 60);
        $key = 'api_rate_limit:' . $apiToken->id;

        $attempts = Cache::get($key, 0);

        if ($attempts >= $rateLimit) {
            return false;
        }

        Cache::put($key, $attempts + 1, now()->addMinute());

        return true;
    }

    /**
     * Update last_used_at timestamp
     */
    private function updateLastUsed(ApiToken $apiToken): void
    {
        // Update without triggering events or updating updated_at
        $apiToken->timestamps = false;
        $apiToken->last_used_at = Carbon::now();
        $apiToken->save();
        $apiToken->timestamps = true;
    }

    /**
     * Log the API request
     */
    private function logRequest(
        ?int $apiTokenId,
        Request $request,
        int $statusCode,
        float $startTime
    ): void {
        $responseTime = (int) ((microtime(true) - $startTime) * 1000); // Convert to milliseconds

        ApiRequestLog::create([
            'api_token_id' => $apiTokenId,
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'query_params' => $request->query->all(),
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'UNAUTHORIZED',
                'message' => $message,
            ],
        ], 401);
    }

    /**
     * Return rate limit exceeded response
     */
    private function rateLimitResponse(): Response
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'message' => 'Too many requests. Please try again later.',
            ],
        ], 429);
    }
}
