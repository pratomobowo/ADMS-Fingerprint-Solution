<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    |
    | This value determines the maximum number of API requests allowed per
    | minute for each API token. This helps prevent abuse and ensures
    | fair usage of the API resources.
    |
    */

    'rate_limit' => env('API_RATE_LIMIT', 60),

    /*
    |--------------------------------------------------------------------------
    | API Token Expiry
    |--------------------------------------------------------------------------
    |
    | This value determines the default number of days before an API token
    | expires. Set to null for tokens that never expire. Individual tokens
    | can override this setting.
    |
    */

    'token_expiry_days' => env('API_TOKEN_EXPIRY_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | These values control the behavior of webhook delivery to external
    | HR applications. Timeout is in seconds, max_retries determines how
    | many times to retry failed deliveries, and retry_backoff defines
    | the delay in seconds between each retry attempt.
    |
    */

    'webhook' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
        'retry_backoff' => array_map(
            'intval',
            explode(',', env('WEBHOOK_RETRY_BACKOFF', '60,300,900'))
        ),
    ],

];
