# Design Document - HR API Integration

## Overview

Sistem integrasi API ini menyediakan dua metode komunikasi antara ADMS dan aplikasi HR eksternal:

1. **Pull Method (API Endpoints)**: HR Application mengambil data absensi melalui RESTful API endpoints
2. **Push Method (Webhooks)**: ADMS mengirim data absensi secara otomatis ke HR Application melalui webhook

Sistem ini dibangun menggunakan Laravel framework yang sudah ada, memanfaatkan Laravel Sanctum untuk autentikasi API, dan Laravel Queue untuk reliable webhook delivery.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        ADMS System                          │
│                                                             │
│  ┌──────────────┐      ┌─────────────────┐                │
│  │   ZKTeco     │─────▶│  iclockController│                │
│  │   Devices    │      │  (Existing)      │                │
│  └──────────────┘      └─────────────────┘                │
│                               │                             │
│                               ▼                             │
│                        ┌─────────────┐                     │
│                        │ Attendance  │                     │
│                        │   Model     │                     │
│                        └─────────────┘                     │
│                               │                             │
│                ┌──────────────┴──────────────┐             │
│                ▼                              ▼             │
│         ┌─────────────┐              ┌──────────────┐     │
│         │ API Service │              │   Webhook    │     │
│         │ (Pull)      │              │   Service    │     │
│         └─────────────┘              │   (Push)     │     │
│                │                      └──────────────┘     │
└────────────────┼─────────────────────────────┬────────────┘
                 │                              │
                 ▼                              ▼
         ┌──────────────┐              ┌──────────────┐
         │ HR App pulls │              │ HR App       │
         │ via API      │              │ receives     │
         └──────────────┘              │ webhook      │
                                       └──────────────┘
```

### Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     API Layer                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  HRApiController                                      │  │
│  │  - getAttendances(Request)                           │  │
│  │  - getAttendancesByEmployee(Request)                 │  │
│  │  - getAttendanceById($id)                            │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  WebhookConfigController                             │  │
│  │  - index()                                           │  │
│  │  - store(Request)                                    │  │
│  │  - update(Request, $id)                              │  │
│  │  - destroy($id)                                      │  │
│  │  - test($id)                                         │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  ApiTokenController                                  │  │
│  │  - index()                                           │  │
│  │  - store(Request)                                    │  │
│  │  - revoke($id)                                       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   Service Layer                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  AttendanceService                                   │  │
│  │  - getAttendancesByDateRange($start, $end, $filters)│  │
│  │  - getAttendancesByEmployee($employeeId, $filters)  │  │
│  │  - formatAttendanceForApi($attendance)              │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  WebhookService                                      │  │
│  │  - sendAttendance($attendance, $webhookConfig)      │  │
│  │  - retryFailedDelivery($deliveryLog)                │  │
│  │  - testWebhook($webhookConfig)                      │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    Job Layer                                │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  SendAttendanceWebhook (Job)                        │  │
│  │  - handle()                                          │  │
│  │  - failed()                                          │  │
│  │  - tries = 3                                         │  │
│  │  - backoff = [60, 300, 900]                         │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   Model Layer                               │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Attendance (Existing)                               │  │
│  │  - employee_id, timestamp, status1-5                 │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  WebhookConfig (New)                                 │  │
│  │  - name, url, is_active, secret_key                  │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  WebhookDeliveryLog (New)                           │  │
│  │  - webhook_config_id, attendance_id, status          │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  ApiToken (New)                                      │  │
│  │  - name, token, last_used_at, expires_at            │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  ApiRequestLog (New)                                 │  │
│  │  - token_id, endpoint, method, status_code           │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. API Endpoints (Pull Method)

#### 1.1 Attendance API Endpoints

**Base URL**: `/api/v1/hr`

**Authentication**: Bearer Token (Laravel Sanctum)

**Endpoints**:

```
GET /api/v1/hr/attendances
GET /api/v1/hr/attendances/{id}
GET /api/v1/hr/employees/{employee_id}/attendances
```

#### 1.2 HRApiController

```php
class HRApiController extends Controller
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {}

    /**
     * Get attendances with filters
     * Query params: start_date, end_date, employee_id, limit, offset
     */
    public function getAttendances(Request $request): JsonResponse
    
    /**
     * Get single attendance by ID
     */
    public function getAttendanceById(int $id): JsonResponse
    
    /**
     * Get attendances for specific employee
     * Query params: start_date, end_date, limit, offset
     */
    public function getAttendancesByEmployee(
        Request $request, 
        int $employeeId
    ): JsonResponse
}
```

#### 1.3 API Response Format

**Success Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "employee_id": "12345",
      "timestamp": "2025-11-15T08:30:00Z",
      "device_sn": "BWN001",
      "status": {
        "status1": true,
        "status2": false,
        "status3": false,
        "status4": false,
        "status5": false
      },
      "created_at": "2025-11-15T08:30:05Z"
    }
  ],
  "meta": {
    "total": 150,
    "count": 50,
    "per_page": 50,
    "current_page": 1,
    "total_pages": 3
  }
}
```

**Error Response**:
```json
{
  "success": false,
  "error": {
    "code": "INVALID_DATE_RANGE",
    "message": "Start date must be before end date",
    "details": {}
  }
}
```

### 2. Webhook System (Push Method)

#### 2.1 Webhook Configuration

**Model**: `WebhookConfig`

```php
class WebhookConfig extends Model
{
    protected $fillable = [
        'name',
        'url',
        'is_active',
        'secret_key',
        'headers',
        'retry_attempts',
        'timeout'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'headers' => 'array',
        'retry_attempts' => 'integer',
        'timeout' => 'integer'
    ];
}
```

#### 2.2 Webhook Delivery Flow

```
Attendance Created
      │
      ▼
Observer detects new attendance
      │
      ▼
Dispatch SendAttendanceWebhook Job
      │
      ▼
Queue processes job
      │
      ├─▶ Success ──▶ Log delivery (status: success)
      │
      └─▶ Failure ──▶ Retry (max 3 times)
                │
                ├─▶ Success ──▶ Log delivery (status: success)
                │
                └─▶ Final Failure ──▶ Log delivery (status: failed)
```

#### 2.3 Webhook Payload Format

```json
{
  "event": "attendance.created",
  "timestamp": "2025-11-15T08:30:05Z",
  "data": {
    "id": 1,
    "employee_id": "12345",
    "timestamp": "2025-11-15T08:30:00Z",
    "device_sn": "BWN001",
    "status": {
      "status1": true,
      "status2": false,
      "status3": false,
      "status4": false,
      "status5": false
    }
  },
  "signature": "sha256_hash_of_payload_with_secret"
}
```

#### 2.4 WebhookService

```php
class WebhookService
{
    /**
     * Send attendance data to webhook URL
     */
    public function sendAttendance(
        Attendance $attendance, 
        WebhookConfig $config
    ): bool
    
    /**
     * Generate signature for webhook payload
     */
    private function generateSignature(
        array $payload, 
        string $secret
    ): string
    
    /**
     * Test webhook configuration
     */
    public function testWebhook(WebhookConfig $config): array
}
```

#### 2.5 SendAttendanceWebhook Job

```php
class SendAttendanceWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 3;
    public $backoff = [60, 300, 900]; // 1min, 5min, 15min
    
    public function __construct(
        public Attendance $attendance,
        public WebhookConfig $webhookConfig
    ) {}
    
    public function handle(WebhookService $webhookService): void
    
    public function failed(Throwable $exception): void
}
```

### 3. Authentication & Authorization

#### 3.1 API Token Management

**Model**: `ApiToken`

```php
class ApiToken extends Model
{
    protected $fillable = [
        'name',
        'token',
        'last_used_at',
        'expires_at',
        'is_active'
    ];
    
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean'
    ];
    
    protected $hidden = ['token'];
}
```

#### 3.2 Token Authentication Middleware

Laravel Sanctum akan digunakan untuk autentikasi API. Custom middleware akan ditambahkan untuk:
- Validasi token
- Update `last_used_at`
- Log API request
- Rate limiting

```php
class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Validate token
        // Update last_used_at
        // Log request
        // Check rate limit
    }
}
```

### 4. Logging & Monitoring

#### 4.1 API Request Logging

**Model**: `ApiRequestLog`

```php
class ApiRequestLog extends Model
{
    protected $fillable = [
        'api_token_id',
        'endpoint',
        'method',
        'query_params',
        'status_code',
        'response_time',
        'ip_address',
        'user_agent'
    ];
    
    protected $casts = [
        'query_params' => 'array',
        'response_time' => 'integer'
    ];
}
```

#### 4.2 Webhook Delivery Logging

**Model**: `WebhookDeliveryLog`

```php
class WebhookDeliveryLog extends Model
{
    protected $fillable = [
        'webhook_config_id',
        'attendance_id',
        'status',
        'http_status_code',
        'response_body',
        'error_message',
        'attempt_number',
        'delivered_at'
    ];
    
    protected $casts = [
        'delivered_at' => 'datetime',
        'attempt_number' => 'integer'
    ];
}
```

### 5. Configuration Management

#### 5.1 Webhook Configuration UI/API

**Endpoints**:
```
GET    /api/v1/admin/webhooks
POST   /api/v1/admin/webhooks
PUT    /api/v1/admin/webhooks/{id}
DELETE /api/v1/admin/webhooks/{id}
POST   /api/v1/admin/webhooks/{id}/test
```

#### 5.2 API Token Management UI/API

**Endpoints**:
```
GET    /api/v1/admin/tokens
POST   /api/v1/admin/tokens
DELETE /api/v1/admin/tokens/{id}
PUT    /api/v1/admin/tokens/{id}/revoke
```

## Data Models

### Database Schema

#### webhook_configs
```sql
CREATE TABLE webhook_configs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    is_active BOOLEAN DEFAULT true,
    secret_key VARCHAR(255) NOT NULL,
    headers JSON NULL,
    retry_attempts INT DEFAULT 3,
    timeout INT DEFAULT 30,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### webhook_delivery_logs
```sql
CREATE TABLE webhook_delivery_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    webhook_config_id BIGINT UNSIGNED NOT NULL,
    attendance_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'success', 'failed') NOT NULL,
    http_status_code INT NULL,
    response_body TEXT NULL,
    error_message TEXT NULL,
    attempt_number INT DEFAULT 1,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (webhook_config_id) REFERENCES webhook_configs(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendances(id) ON DELETE CASCADE
);
```

#### api_tokens
```sql
CREATE TABLE api_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

#### api_request_logs
```sql
CREATE TABLE api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    api_token_id BIGINT UNSIGNED NULL,
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    query_params JSON NULL,
    status_code INT NOT NULL,
    response_time INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL,
    FOREIGN KEY (api_token_id) REFERENCES api_tokens(id) ON DELETE SET NULL
);
```

## Error Handling

### API Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| INVALID_TOKEN | 401 | Token tidak valid atau expired |
| UNAUTHORIZED | 401 | Tidak memiliki akses |
| INVALID_DATE_RANGE | 400 | Format tanggal tidak valid |
| RESOURCE_NOT_FOUND | 404 | Data tidak ditemukan |
| RATE_LIMIT_EXCEEDED | 429 | Terlalu banyak request |
| INTERNAL_ERROR | 500 | Server error |

### Webhook Error Handling

1. **Connection Timeout**: Retry dengan backoff exponential
2. **HTTP 4xx**: Log error, tidak retry (kecuali 429)
3. **HTTP 5xx**: Retry hingga max attempts
4. **Network Error**: Retry hingga max attempts

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": {
      "field": "Additional context"
    }
  }
}
```

## Testing Strategy

### Unit Tests

1. **AttendanceService Tests**
   - Test filtering by date range
   - Test filtering by employee
   - Test data formatting

2. **WebhookService Tests**
   - Test payload generation
   - Test signature generation
   - Test webhook delivery

3. **Model Tests**
   - Test relationships
   - Test scopes
   - Test accessors/mutators

### Integration Tests

1. **API Endpoint Tests**
   - Test authentication
   - Test authorization
   - Test response format
   - Test pagination
   - Test filtering
   - Test error handling

2. **Webhook Delivery Tests**
   - Test successful delivery
   - Test retry mechanism
   - Test failure logging
   - Test queue processing

### Feature Tests

1. **End-to-End API Flow**
   - Create token → Make request → Verify response
   - Invalid token → Verify 401 response

2. **End-to-End Webhook Flow**
   - Create attendance → Verify webhook sent → Check logs

## Security Considerations

### API Security

1. **Authentication**: Bearer token dengan Laravel Sanctum
2. **Rate Limiting**: 60 requests per minute per token
3. **HTTPS Only**: Enforce HTTPS di production
4. **Token Expiration**: Optional expiration date untuk tokens
5. **IP Whitelisting**: Optional IP restriction per token

### Webhook Security

1. **Signature Verification**: HMAC SHA256 signature
2. **HTTPS Only**: Hanya kirim ke HTTPS URLs
3. **Secret Key**: Unique secret per webhook config
4. **Timeout**: 30 detik default timeout
5. **Payload Size Limit**: Max 1MB per payload

## Performance Considerations

### API Performance

1. **Database Indexing**:
   - Index pada `attendances.employee_id`
   - Index pada `attendances.timestamp`
   - Composite index pada `(employee_id, timestamp)`

2. **Query Optimization**:
   - Pagination untuk large datasets
   - Eager loading untuk relationships
   - Select only needed columns

3. **Caching**:
   - Cache frequently accessed data
   - Cache duration: 5 minutes

### Webhook Performance

1. **Queue System**: Menggunakan Laravel Queue (database/redis)
2. **Batch Processing**: Group multiple attendances jika diperlukan
3. **Async Processing**: Webhook tidak block attendance creation
4. **Connection Pooling**: Reuse HTTP connections

## Configuration

### Environment Variables

```env
# API Configuration
API_RATE_LIMIT=60
API_TOKEN_EXPIRY_DAYS=365

# Webhook Configuration
WEBHOOK_TIMEOUT=30
WEBHOOK_MAX_RETRIES=3
WEBHOOK_RETRY_BACKOFF=60,300,900

# Queue Configuration
QUEUE_CONNECTION=database
```

### Config File: `config/hr_api.php`

```php
return [
    'rate_limit' => env('API_RATE_LIMIT', 60),
    'token_expiry_days' => env('API_TOKEN_EXPIRY_DAYS', 365),
    'webhook' => [
        'timeout' => env('WEBHOOK_TIMEOUT', 30),
        'max_retries' => env('WEBHOOK_MAX_RETRIES', 3),
        'retry_backoff' => explode(',', env('WEBHOOK_RETRY_BACKOFF', '60,300,900')),
    ],
];
```

## API Documentation

API documentation akan disediakan menggunakan format OpenAPI 3.0 (Swagger) dan akan di-host di endpoint `/api/documentation`.

### Documentation Sections

1. **Authentication**: Cara mendapatkan dan menggunakan API token
2. **Endpoints**: Detail setiap endpoint dengan examples
3. **Webhooks**: Format payload dan signature verification
4. **Error Codes**: Daftar lengkap error codes
5. **Rate Limiting**: Informasi tentang rate limits
6. **Changelog**: Version history dan breaking changes

## Deployment Considerations

### Migration Strategy

1. Run database migrations
2. Generate initial API token untuk testing
3. Configure queue worker
4. Setup monitoring untuk webhook delivery
5. Deploy API documentation

### Monitoring

1. **API Metrics**:
   - Request count per endpoint
   - Response time
   - Error rate
   - Token usage

2. **Webhook Metrics**:
   - Delivery success rate
   - Retry count
   - Failed deliveries
   - Average delivery time

### Rollback Plan

1. Disable webhook delivery via config
2. Revoke API tokens if needed
3. Rollback database migrations
4. Restore previous code version
