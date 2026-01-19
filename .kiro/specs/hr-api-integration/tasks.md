# Implementation Plan - HR API Integration

- [x] 1. Setup database migrations dan models
- [x] 1.1 Buat migration untuk tabel webhook_configs
  - Buat file migration dengan schema: name, url, is_active, secret_key, headers, retry_attempts, timeout
  - _Requirements: 2.2, 3.2_

- [x] 1.2 Buat migration untuk tabel webhook_delivery_logs
  - Buat file migration dengan schema: webhook_config_id, attendance_id, status, http_status_code, response_body, error_message, attempt_number, delivered_at
  - Tambahkan foreign keys ke webhook_configs dan attendances
  - _Requirements: 2.4, 5.2_

- [x] 1.3 Buat migration untuk tabel api_tokens
  - Buat file migration dengan schema: name, token, last_used_at, expires_at, is_active
  - Tambahkan unique index pada kolom token
  - _Requirements: 6.1, 6.4_

- [x] 1.4 Buat migration untuk tabel api_request_logs
  - Buat file migration dengan schema: api_token_id, endpoint, method, query_params, status_code, response_time, ip_address, user_agent
  - Tambahkan foreign key ke api_tokens
  - _Requirements: 5.1, 5.4_

- [x] 1.5 Buat model WebhookConfig dengan fillable, casts, dan relationships
  - Implementasi model dengan protected fillable dan casts untuk is_active, headers, retry_attempts, timeout
  - Tambahkan relationship hasMany ke WebhookDeliveryLog
  - _Requirements: 2.2, 3.2_

- [x] 1.6 Buat model WebhookDeliveryLog dengan fillable, casts, dan relationships
  - Implementasi model dengan protected fillable dan casts untuk delivered_at, attempt_number
  - Tambahkan relationship belongsTo ke WebhookConfig dan Attendance
  - _Requirements: 2.4, 5.2_

- [x] 1.7 Buat model ApiToken dengan fillable, casts, dan relationships
  - Implementasi model dengan protected fillable, casts untuk timestamps dan is_active, dan hidden untuk token
  - Tambahkan relationship hasMany ke ApiRequestLog
  - _Requirements: 6.1, 6.4_

- [x] 1.8 Buat model ApiRequestLog dengan fillable, casts, dan relationships
  - Implementasi model dengan protected fillable dan casts untuk query_params, response_time
  - Tambahkan relationship belongsTo ke ApiToken
  - _Requirements: 5.1, 5.4_

- [x] 1.9 Tambahkan database indexes untuk performance optimization
  - Tambahkan index pada attendances.employee_id dan attendances.timestamp
  - Tambahkan composite index pada (employee_id, timestamp)
  - _Requirements: 1.2, 1.3_

- [x] 2. Implementasi service layer untuk business logic
- [x] 2.1 Buat AttendanceService untuk handle attendance data operations
  - Implementasi method getAttendancesByDateRange dengan filtering dan pagination
  - Implementasi method getAttendancesByEmployee dengan filtering dan pagination
  - Implementasi method formatAttendanceForApi untuk transform data ke format API response
  - _Requirements: 1.1, 1.2, 1.3_

- [x] 2.2 Buat WebhookService untuk handle webhook delivery
  - Implementasi method sendAttendance untuk mengirim data ke webhook URL dengan HTTP client
  - Implementasi method generateSignature untuk HMAC SHA256 signature
  - Implementasi method testWebhook untuk test webhook configuration
  - Tambahkan error handling dan timeout configuration
  - _Requirements: 2.1, 2.2, 2.3_

- [x] 3. Implementasi API endpoints untuk pull method
- [x] 3.1 Buat HRApiController dengan dependency injection AttendanceService
  - Setup constructor dengan AttendanceService injection
  - _Requirements: 1.1_

- [x] 3.2 Implementasi endpoint GET /api/v1/hr/attendances
  - Implementasi method getAttendances dengan request validation untuk start_date, end_date, employee_id, limit, offset
  - Return JSON response dengan format success, data, dan meta pagination
  - Tambahkan error handling untuk invalid parameters
  - _Requirements: 1.1, 1.3, 1.4_

- [x] 3.3 Implementasi endpoint GET /api/v1/hr/attendances/{id}
  - Implementasi method getAttendanceById dengan parameter id
  - Return JSON response dengan single attendance data
  - Tambahkan error handling untuk resource not found
  - _Requirements: 1.1, 1.4_

- [x] 3.4 Implementasi endpoint GET /api/v1/hr/employees/{employee_id}/attendances
  - Implementasi method getAttendancesByEmployee dengan parameter employee_id dan query params
  - Return JSON response dengan format success, data, dan meta pagination
  - Tambahkan error handling untuk invalid employee_id
  - _Requirements: 1.2, 1.3, 1.4_

- [x] 3.5 Tambahkan API routes di routes/api.php dengan middleware auth:sanctum
  - Register semua HR API routes dengan prefix /v1/hr
  - Apply auth:sanctum middleware untuk authentication
  - Apply rate limiting middleware
  - _Requirements: 1.5, 6.1, 6.2_

- [x] 4. Implementasi webhook push method dengan queue system
- [x] 4.1 Buat SendAttendanceWebhook job class
  - Implementasi job dengan ShouldQueue interface
  - Setup properties: tries = 3, backoff = [60, 300, 900]
  - Implementasi constructor dengan Attendance dan WebhookConfig parameters
  - _Requirements: 2.2, 2.3_

- [x] 4.2 Implementasi handle method di SendAttendanceWebhook job
  - Inject WebhookService ke handle method
  - Call WebhookService->sendAttendance dengan attendance dan webhook config
  - Log successful delivery ke WebhookDeliveryLog dengan status success
  - _Requirements: 2.1, 2.2, 2.4_

- [x] 4.3 Implementasi failed method di SendAttendanceWebhook job
  - Log failed delivery ke WebhookDeliveryLog dengan status failed dan error message
  - _Requirements: 2.3, 2.4_

- [x] 4.4 Buat AttendanceObserver untuk trigger webhook saat attendance created
  - Implementasi created method untuk detect new attendance
  - Check active webhook configs dan dispatch SendAttendanceWebhook job
  - _Requirements: 2.1, 2.5_

- [x] 4.5 Register AttendanceObserver di AppServiceProvider
  - Tambahkan Attendance::observe(AttendanceObserver::class) di boot method
  - _Requirements: 2.1_

- [x] 5. Implementasi authentication dan authorization
- [x] 5.1 Install dan configure Laravel Sanctum
  - Run php artisan install:api jika belum ada
  - Publish sanctum configuration
  - _Requirements: 6.1_

- [x] 5.2 Buat custom middleware ApiTokenAuth untuk token validation
  - Implementasi middleware untuk validate token dari header Authorization
  - Update last_used_at pada ApiToken saat request berhasil
  - Log request ke ApiRequestLog dengan endpoint, method, status_code, response_time
  - Tambahkan rate limiting check
  - _Requirements: 6.1, 6.2, 6.3, 5.1_

- [x] 5.3 Register ApiTokenAuth middleware di Kernel.php
  - Tambahkan middleware ke $middlewareAliases
  - _Requirements: 6.1_

- [x] 6. Implementasi management endpoints untuk webhook dan token
- [x] 6.1 Buat WebhookConfigController untuk CRUD webhook configurations
  - Implementasi index method untuk list semua webhook configs
  - Implementasi store method untuk create webhook config dengan validation
  - Implementasi update method untuk update webhook config
  - Implementasi destroy method untuk delete webhook config
  - _Requirements: 3.1, 3.2, 3.3, 3.4_

- [x] 6.2 Implementasi test webhook endpoint di WebhookConfigController
  - Implementasi method test untuk test webhook configuration
  - Call WebhookService->testWebhook dan return hasil test
  - _Requirements: 3.1_

- [x] 6.3 Buat ApiTokenController untuk manage API tokens
  - Implementasi index method untuk list semua tokens
  - Implementasi store method untuk generate new token dengan random string
  - Implementasi revoke method untuk revoke/deactivate token
  - _Requirements: 3.2, 6.4, 6.5_

- [x] 6.4 Tambahkan admin routes untuk webhook dan token management
  - Register WebhookConfigController routes dengan prefix /v1/admin/webhooks
  - Register ApiTokenController routes dengan prefix /v1/admin/tokens
  - Apply authentication middleware
  - _Requirements: 3.1, 3.2_

- [x] 7. Implementasi logging dan monitoring
- [x] 7.1 Buat LoggingService untuk centralized logging operations
  - Implementasi method logApiRequest untuk log API requests
  - Implementasi method logWebhookDelivery untuk log webhook deliveries
  - _Requirements: 5.1, 5.2, 5.4_

- [x] 7.2 Integrate logging ke HRApiController
  - Tambahkan logging di setiap endpoint untuk track requests
  - Log response time dan status code
  - _Requirements: 5.1_

- [x] 7.3 Integrate logging ke WebhookService
  - Log setiap webhook delivery attempt dengan status
  - Log error details untuk failed deliveries
  - _Requirements: 5.2, 5.4_

- [x] 8. Buat configuration file dan environment setup
- [x] 8.1 Buat config file config/hr_api.php
  - Define configuration untuk rate_limit, token_expiry_days, webhook timeout, max_retries, retry_backoff
  - Load values dari environment variables dengan defaults
  - _Requirements: 3.2, 6.1_

- [x] 8.2 Update .env.example dengan HR API configuration variables
  - Tambahkan API_RATE_LIMIT, API_TOKEN_EXPIRY_DAYS, WEBHOOK_TIMEOUT, WEBHOOK_MAX_RETRIES, WEBHOOK_RETRY_BACKOFF
  - _Requirements: 3.2_

- [x] 9. Implementasi request validation
- [x] 9.1 Buat form request AttendanceQueryRequest untuk validate API query parameters
  - Validate start_date, end_date, employee_id, limit, offset dengan rules yang sesuai
  - Tambahkan custom error messages
  - _Requirements: 1.3, 1.4, 1.5_

- [x] 9.2 Buat form request WebhookConfigRequest untuk validate webhook configuration
  - Validate name, url (must be HTTPS), secret_key, headers, retry_attempts, timeout
  - Tambahkan custom validation rule untuk HTTPS URL
  - _Requirements: 3.3, 3.5_

- [x] 9.3 Buat form request ApiTokenRequest untuk validate token creation
  - Validate name, expires_at dengan rules yang sesuai
  - _Requirements: 3.2_

- [x] 10. Implementasi error handling dan response formatting
- [x] 10.1 Buat ApiResponse helper class untuk standardize API responses
  - Implementasi static method success untuk success responses dengan data dan meta
  - Implementasi static method error untuk error responses dengan code, message, details
  - _Requirements: 1.4, 1.5_

- [x] 10.2 Buat custom exception handler untuk API errors
  - Extend Laravel exception handler untuk catch API-specific exceptions
  - Return formatted error responses menggunakan ApiResponse helper
  - Handle authentication errors (401), validation errors (400), not found (404), rate limit (429)
  - _Requirements: 1.5, 6.2, 6.5_

- [x] 10.3 Integrate ApiResponse ke HRApiController
  - Update semua controller methods untuk use ApiResponse helper
  - _Requirements: 1.4_

- [x] 11. Buat API documentation dengan OpenAPI/Swagger
- [x] 11.1 Install dan configure L5-Swagger package
  - Install package via composer
  - Publish configuration
  - _Requirements: 4.1_

- [x] 11.2 Tambahkan OpenAPI annotations ke HRApiController
  - Annotate semua endpoints dengan @OA\Get, @OA\Post, dll
  - Define request parameters, response schemas, dan error responses
  - _Requirements: 4.1, 4.2, 4.4_

- [x] 11.3 Tambahkan OpenAPI annotations untuk authentication
  - Define security scheme untuk Bearer token
  - Document authentication flow
  - _Requirements: 4.4_

- [x] 11.4 Tambahkan documentation untuk webhook payload format
  - Document webhook event structure dan signature verification
  - Tambahkan examples untuk webhook payload
  - _Requirements: 4.3_

- [x] 11.5 Generate dan publish API documentation
  - Run php artisan l5-swagger:generate
  - Setup route untuk access documentation di /api/documentation
  - _Requirements: 4.1, 4.5_

- [x] 12. Testing dan quality assurance
- [x] 12.1 Buat unit tests untuk AttendanceService
  - Test getAttendancesByDateRange dengan berbagai filters
  - Test getAttendancesByEmployee dengan berbagai scenarios
  - Test formatAttendanceForApi output format
  - _Requirements: 1.1, 1.2_

- [x] 12.2 Buat unit tests untuk WebhookService
  - Test sendAttendance dengan mock HTTP client
  - Test generateSignature dengan known inputs
  - Test testWebhook functionality
  - _Requirements: 2.1, 2.2_

- [x] 12.3 Buat feature tests untuk API endpoints
  - Test GET /api/v1/hr/attendances dengan valid token dan berbagai query params
  - Test GET /api/v1/hr/attendances/{id} dengan valid dan invalid IDs
  - Test GET /api/v1/hr/employees/{employee_id}/attendances
  - Test authentication failures dengan invalid token
  - Test rate limiting behavior
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 6.2_

- [x] 12.4 Buat feature tests untuk webhook delivery
  - Test webhook dispatch saat attendance created
  - Test retry mechanism dengan mock failed deliveries
  - Test logging untuk successful dan failed deliveries
  - _Requirements: 2.1, 2.2, 2.3, 2.4_

- [x] 12.5 Buat feature tests untuk management endpoints
  - Test CRUD operations untuk webhook configs
  - Test token generation dan revocation
  - Test webhook test endpoint
  - _Requirements: 3.1, 3.2, 3.3, 3.4_
