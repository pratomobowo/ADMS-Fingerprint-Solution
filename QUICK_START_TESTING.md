# Quick Start - Testing Guide

Panduan cepat untuk testing sebelum push ke production.

## 🚀 Quick Test (5 menit)

```bash
# 1. Run comprehensive test
bash scripts/pre-production-test.sh

# 2. Jika semua pass, Anda siap deploy!
```

## 📋 Manual Testing Steps

### 1. Test Semua Unit & Feature Tests
```bash
php artisan test
```

**Expected Output:**
```
Tests:    53 passed (284 assertions)
Duration: ~3s
```

### 2. Generate & Verify API Documentation
```bash
# Generate documentation
php artisan l5-swagger:generate

# Verify JSON valid
php -r "json_decode(file_get_contents('storage/api-docs/api-docs.json')); echo json_last_error() === 0 ? 'Valid JSON' : 'Invalid JSON';"

# Check endpoints count
jq -r '.paths | keys | length' storage/api-docs/api-docs.json
# Should output: 8
```

### 3. Verify Routes
```bash
php artisan route:list | grep "api/v1"
```

**Expected:** 12 routes

### 4. Check Environment Configuration
```bash
# Verify required variables
grep -E "^(API_RATE_LIMIT|WEBHOOK_TIMEOUT|API_TOKEN_EXPIRY_DAYS)=" .env
```

**Expected Output:**
```
API_RATE_LIMIT=60
WEBHOOK_TIMEOUT=30
API_TOKEN_EXPIRY_DAYS=365
```

## 🧪 Test Individual Components

### Test Unit Tests Only
```bash
php artisan test --testsuite=Unit
```

### Test Feature Tests Only
```bash
php artisan test --testsuite=Feature
```

### Test Specific File
```bash
php artisan test tests/Feature/HRApiEndpointsTest.php
```

### Test with Coverage (requires xdebug)
```bash
php artisan test --coverage
```

## 🔍 Verify Specific Features

### 1. Authentication
```bash
php artisan test --filter="authentication"
```

### 2. Webhook System
```bash
php artisan test tests/Feature/WebhookDeliveryTest.php
php artisan test tests/Unit/WebhookServiceTest.php
```

### 3. API Endpoints
```bash
php artisan test tests/Feature/HRApiEndpointsTest.php
```

### 4. Management Endpoints
```bash
php artisan test tests/Feature/ManagementEndpointsTest.php
```

## 📊 Check Test Results

### View Detailed Test Output
```bash
php artisan test --verbose
```

### Stop on First Failure
```bash
php artisan test --stop-on-failure
```

### Run Tests in Parallel (faster)
```bash
php artisan test --parallel
```

## 🔧 Troubleshooting

### Clear All Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Check Database Connection
```bash
php artisan migrate:status
```

### Verify Composer Dependencies
```bash
composer validate
composer audit
```

### Check File Permissions
```bash
ls -la storage/logs
ls -la storage/api-docs
```

## ✅ Pre-Production Checklist

Sebelum push ke production, pastikan:

- [ ] ✅ All tests passing (53/53)
- [ ] ✅ API documentation generated
- [ ] ✅ JSON structure valid
- [ ] ✅ No security vulnerabilities
- [ ] ✅ Environment variables configured
- [ ] ✅ Database migrations up to date
- [ ] ✅ File permissions correct
- [ ] ✅ Routes registered correctly

## 🚀 Ready to Deploy?

Jika semua checklist di atas ✅, Anda siap untuk:

1. **Review** `PRE_DEPLOYMENT_CHECKLIST.md`
2. **Backup** database production
3. **Deploy** mengikuti deployment steps
4. **Monitor** logs setelah deployment

## 📚 Documentation

- **API Documentation:** `/api/documentation` (setelah deploy)
- **Full Test Results:** `TEST_RESULTS_SUMMARY.md`
- **Deployment Guide:** `PRE_DEPLOYMENT_CHECKLIST.md`
- **API Guide:** `API_DOCUMENTATION.md`

## 🆘 Need Help?

### Common Commands
```bash
# Run all tests
php artisan test

# Run comprehensive pre-production test
bash scripts/pre-production-test.sh

# Test API endpoints (requires running server)
bash scripts/test-api-endpoints.sh http://localhost YOUR_TOKEN

# Verify API documentation
bash scripts/verify-api-docs.sh

# Clear all caches
php artisan optimize:clear
```

### Check Logs
```bash
# Application logs
tail -f storage/logs/laravel.log

# Webhook logs
tail -f storage/logs/webhook.log
```

---

**Quick Answer:** Jalankan `bash scripts/pre-production-test.sh` - jika semua pass, Anda siap production! ✅
