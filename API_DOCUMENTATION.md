# ADMS HR API Documentation

## Overview

The ADMS HR API provides integration between the Attendance Data Management System (ADMS) and external HR applications. The API supports two integration methods:

1. **Pull Method**: HR applications can retrieve attendance data via RESTful API endpoints
2. **Push Method**: ADMS automatically sends attendance data to configured webhook endpoints

## Accessing the Documentation

### Interactive API Documentation (Swagger UI)

The interactive API documentation is available at:

```
http://your-domain.com/api/documentation
```

This provides:
- Complete API endpoint reference
- Request/response examples
- Interactive testing interface
- Authentication guide
- Webhook integration guide

### Generating Documentation

To regenerate the API documentation after making changes:

```bash
php artisan l5-swagger:generate
```

## Quick Start

### 1. Authentication

All API endpoints require Bearer token authentication.

#### Obtaining a Token

Contact your system administrator to generate an API token via:
```
POST /api/v1/admin/tokens
```

#### Using the Token

Include the token in the Authorization header:
```bash
curl -X GET "https://api.example.com/api/v1/hr/attendances" \
     -H "Authorization: Bearer your-token-here"
```

### 2. Pull Method - Retrieving Attendance Data

#### Get All Attendances
```bash
GET /api/v1/hr/attendances?start_date=2025-11-01&end_date=2025-11-15&limit=50
```

#### Get Attendance by ID
```bash
GET /api/v1/hr/attendances/{id}
```

#### Get Attendances by Employee
```bash
GET /api/v1/hr/employees/{employee_id}/attendances?start_date=2025-11-01
```

### 3. Push Method - Webhook Integration

#### Configure Webhook

Create a webhook configuration:
```bash
POST /api/v1/admin/webhooks
Content-Type: application/json

{
  "name": "HR System Webhook",
  "url": "https://hr.example.com/api/webhooks/attendance",
  "secret_key": "your-secret-key",
  "is_active": true,
  "retry_attempts": 3,
  "timeout": 30
}
```

#### Webhook Payload Format

When an attendance record is created, ADMS sends:
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
    },
    "created_at": "2025-11-15T08:30:05Z"
  },
  "signature": "hmac-sha256-signature"
}
```

#### Verifying Webhook Signature

**PHP Example:**
```php
$payload = json_decode($request->getContent(), true);
$receivedSignature = $payload['signature'];
unset($payload['signature']);

$calculatedSignature = hash_hmac(
    'sha256',
    json_encode($payload),
    'your-secret-key'
);

if (hash_equals($calculatedSignature, $receivedSignature)) {
    // Signature is valid, process the webhook
}
```

**Node.js Example:**
```javascript
const crypto = require('crypto');

const payload = req.body;
const receivedSignature = payload.signature;
delete payload.signature;

const calculatedSignature = crypto
  .createHmac('sha256', 'your-secret-key')
  .update(JSON.stringify(payload))
  .digest('hex');

if (calculatedSignature === receivedSignature) {
  // Signature is valid, process the webhook
}
```

## API Endpoints

### Attendance Endpoints
- `GET /api/v1/hr/attendances` - List attendances with filters
- `GET /api/v1/hr/attendances/{id}` - Get single attendance
- `GET /api/v1/hr/employees/{employee_id}/attendances` - Get employee attendances

### Webhook Management (Admin)
- `GET /api/v1/admin/webhooks` - List webhook configurations
- `POST /api/v1/admin/webhooks` - Create webhook configuration
- `PUT /api/v1/admin/webhooks/{id}` - Update webhook configuration
- `DELETE /api/v1/admin/webhooks/{id}` - Delete webhook configuration
- `POST /api/v1/admin/webhooks/{id}/test` - Test webhook configuration

### Token Management (Admin)
- `GET /api/v1/admin/tokens` - List API tokens
- `POST /api/v1/admin/tokens` - Generate new token
- `PUT /api/v1/admin/tokens/{id}/revoke` - Revoke token

## Rate Limiting

- Default: 60 requests per minute per token
- Exceeding the limit returns HTTP 429 (Too Many Requests)

## Error Handling

All error responses follow this format:
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message",
    "details": {}
  }
}
```

### Common Error Codes
- `INVALID_TOKEN` (401) - Invalid or expired token
- `UNAUTHORIZED` (401) - No access permission
- `INVALID_DATE_RANGE` (400) - Invalid date format
- `RESOURCE_NOT_FOUND` (404) - Resource not found
- `RATE_LIMIT_EXCEEDED` (429) - Too many requests
- `INTERNAL_ERROR` (500) - Server error

## Testing

### Test Webhook Configuration
```bash
POST /api/v1/admin/webhooks/{id}/test
```

This sends a test payload to verify your webhook endpoint is working correctly.

## Support

For technical support or questions about the API:
- Email: admin@adms.com
- Documentation: http://your-domain.com/api/documentation

## Version History

### Version 1.0.0
- Initial release
- Pull method (API endpoints)
- Push method (Webhooks)
- Bearer token authentication
- Rate limiting
- Comprehensive documentation
