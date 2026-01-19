# Pre-Deployment Checklist untuk Production

## ✅ Testing Completed

### Unit Tests
- ✓ AttendanceServiceTest (8 tests, 46 assertions)
- ✓ WebhookServiceTest (6 tests)
- ✓ All unit tests passed

### Feature Tests  
- ✓ HRApiEndpointsTest (13 tests)
- ✓ ManagementEndpointsTest (15 tests)
- ✓ WebhookDeliveryTest (10 tests)
- ✓ All feature tests passed (38 tests, 238 assertions)

### API Documentation
- ✓ Swagger documentation generated successfully
- ✓ JSON structure valid
- ✓ 12 endpoints documented
- ✓ 6 schemas documented

### Security
- ✓ No security vulnerabilities found (composer audit)
- ✓ API token authentication working
- ✓ Rate limiting configured
- ✓ HTTPS validation for webhooks

## 📋 Pre-Deployment Steps

### 1. Environment Configuration

**Production .env file harus memiliki:**

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-production-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=your-production-db-host
DB_PORT=3306
DB_DATABASE=your-production-db
DB_USERNAME=your-db-user
DB_PASSWORD=your-secure-password

# HR API Configuration
API_RATE_LIMIT=60
API_TOKEN_EXPIRY_DAYS=365
WEBHOOK_TIMEOUT=30
WEBHOOK_MAX_RETRIES=3
WEBHOOK_RETRY_BACKOFF=60,300,900

# API Documentation
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_CONST_HOST=https://your-production-domain.com

# Queue (Recommended for production)
QUEUE_CONNECTION=database  # atau redis untuk performa lebih baik

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=warning
```

### 2. Database Migration

```bash
# Backup database terlebih dahulu!
php artisan db:backup  # atau gunakan tool backup Anda

# Jalankan migrations
php artisan migrate --force

# Verify migrations
php artisan migrate:status
```

### 3. Composer Dependencies

```bash
# Install production dependencies only
composer install --no-dev --optimize-autoloader

# Verify installation
composer validate
```

### 4. Optimize Application

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Generate API documentation
php artisan l5-swagger:generate
```

### 5. File Permissions

```bash
# Set proper permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Verify writable directories
ls -la storage/logs
ls -la storage/api-docs
```

### 6. Web Server Configuration

**Nginx Example:**
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 7. SSL Certificate

```bash
# Install SSL certificate (Let's Encrypt example)
certbot --nginx -d your-domain.com
```

### 8. Queue Worker (Jika menggunakan queue)

```bash
# Setup supervisor untuk queue worker
sudo nano /etc/supervisor/conf.d/adms-worker.conf
```

**Supervisor Configuration:**
```ini
[program:adms-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start adms-worker:*
```

### 9. Monitoring & Logging

**Setup log rotation:**
```bash
sudo nano /etc/logrotate.d/adms
```

```
/path/to/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
}
```

### 10. Initial Setup di Production

```bash
# Generate API token pertama untuk admin
php artisan tinker
>>> $token = \App\Models\ApiToken::create([
...     'name' => 'Initial Admin Token',
...     'token' => \Illuminate\Support\Str::random(64),
...     'expires_at' => now()->addYear(),
...     'is_active' => true
... ]);
>>> echo $token->token;
```

**Simpan token ini dengan aman!**

## 🧪 Post-Deployment Testing

### 1. Health Check

```bash
# Test API endpoint
curl -X GET https://your-domain.com/api/v1/attendances \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 2. Verify API Documentation

Akses: `https://your-domain.com/api/documentation`

### 3. Test Webhook

```bash
# Create webhook config
curl -X POST https://your-domain.com/api/v1/admin/webhooks \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Webhook",
    "url": "https://webhook.site/your-unique-url",
    "secret": "your-secret-key",
    "is_active": true
  }'

# Test webhook
curl -X POST https://your-domain.com/api/v1/admin/webhooks/{id}/test \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Monitor Logs

```bash
# Watch application logs
tail -f storage/logs/laravel.log

# Watch webhook logs
tail -f storage/logs/webhook.log

# Watch queue worker logs (if using queue)
tail -f storage/logs/worker.log
```

## 🔒 Security Checklist

- [ ] APP_DEBUG=false di production
- [ ] APP_ENV=production
- [ ] Database credentials aman
- [ ] .env file tidak ter-commit ke git
- [ ] SSL certificate terpasang
- [ ] Firewall dikonfigurasi dengan benar
- [ ] Rate limiting aktif
- [ ] API tokens menggunakan HTTPS only
- [ ] Webhook URLs harus HTTPS
- [ ] File permissions sudah benar (755/644)
- [ ] Sensitive directories tidak accessible dari web

## 📊 Monitoring Recommendations

### Metrics to Monitor:
1. **API Response Time**
   - Target: < 200ms untuk queries sederhana
   - Alert jika > 1000ms

2. **Webhook Success Rate**
   - Target: > 95%
   - Alert jika < 90%

3. **Database Connections**
   - Monitor connection pool usage
   - Alert jika mendekati limit

4. **Disk Space**
   - Monitor storage/logs directory
   - Setup log rotation

5. **Queue Length** (jika menggunakan queue)
   - Monitor pending jobs
   - Alert jika > 1000 jobs

### Recommended Tools:
- Laravel Telescope (development/staging only)
- Laravel Horizon (untuk Redis queue)
- New Relic / DataDog (APM)
- Sentry (Error tracking)
- Uptime monitoring (Pingdom, UptimeRobot)

## 🚀 Deployment Command Summary

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --no-dev --optimize-autoloader

# 3. Run migrations
php artisan migrate --force

# 4. Clear and cache
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Generate API docs
php artisan l5-swagger:generate

# 6. Restart services
sudo supervisorctl restart adms-worker:*
sudo systemctl reload php8.1-fpm
sudo systemctl reload nginx
```

## 📞 Rollback Plan

Jika terjadi masalah:

```bash
# 1. Rollback code
git checkout previous-stable-tag

# 2. Rollback database (jika perlu)
php artisan migrate:rollback --step=1

# 3. Clear caches
php artisan config:clear
php artisan cache:clear

# 4. Restart services
sudo supervisorctl restart adms-worker:*
sudo systemctl reload php8.1-fpm
```

## ✅ Final Verification

Sebelum menganggap deployment sukses, pastikan:

- [ ] API endpoints merespon dengan benar
- [ ] API documentation accessible
- [ ] Webhook delivery berfungsi
- [ ] Logs tidak menunjukkan error
- [ ] Database queries berjalan normal
- [ ] Queue workers running (jika applicable)
- [ ] SSL certificate valid
- [ ] Monitoring alerts configured

---

**Last Updated:** 2024
**Tested By:** Pre-production test script
**Status:** ✅ Ready for Production
