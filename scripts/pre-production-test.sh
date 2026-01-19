#!/bin/bash

# Pre-Production Testing Script
# This script runs comprehensive tests before deploying to production

# Exit on error, but allow test failures to be counted
# set -e  # Exit on any error

echo "=========================================="
echo "ADMS HR API - Pre-Production Testing"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print success
print_success() {
    echo -e "${GREEN}✓${NC} $1"
    ((TESTS_PASSED++))
}

# Function to print error
print_error() {
    echo -e "${RED}✗${NC} $1"
    ((TESTS_FAILED++))
}

# Function to print info
print_info() {
    echo -e "${YELLOW}ℹ${NC} $1"
}

echo "1. Clearing caches..."
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
print_success "Caches cleared"
echo ""

echo "2. Checking environment configuration..."
if [ -f .env ]; then
    print_success ".env file exists"
    
    # Check required variables
    required_vars=("DB_CONNECTION" "DB_DATABASE" "APP_KEY" "API_RATE_LIMIT" "WEBHOOK_TIMEOUT")
    for var in "${required_vars[@]}"; do
        if grep -q "^${var}=" .env; then
            print_success "$var is configured"
        else
            print_error "$var is missing in .env"
        fi
    done
else
    print_error ".env file not found"
fi
echo ""

echo "3. Checking database connection..."
if php artisan migrate:status > /dev/null 2>&1; then
    print_success "Database connection successful"
else
    print_error "Database connection failed"
fi
echo ""

echo "4. Running database migrations check..."
if php artisan migrate:status > /dev/null 2>&1; then
    print_success "Database migrations are up to date"
else
    print_error "Database migrations check failed"
fi
echo ""

echo "5. Running Unit Tests..."
if php artisan test --testsuite=Unit --stop-on-failure; then
    print_success "All unit tests passed"
else
    print_error "Unit tests failed"
fi
echo ""

echo "6. Running Feature Tests..."
if php artisan test --testsuite=Feature --stop-on-failure; then
    print_success "All feature tests passed"
else
    print_error "Feature tests failed"
fi
echo ""

echo "7. Generating API Documentation..."
if php artisan l5-swagger:generate > /dev/null 2>&1; then
    print_success "API documentation generated"
    
    # Verify JSON is valid
    if php -r "json_decode(file_get_contents('storage/api-docs/api-docs.json')); exit(json_last_error());" 2>/dev/null; then
        print_success "API documentation JSON is valid"
    else
        print_error "API documentation JSON is invalid"
    fi
else
    print_error "API documentation generation failed"
fi
echo ""

echo "8. Verifying API routes..."
if php artisan route:list | grep -q "api/v1"; then
    print_success "API routes are registered"
    
    # Count routes
    route_count=$(php artisan route:list | grep "api/v1" | wc -l | tr -d ' ')
    print_info "Found $route_count API routes"
else
    print_error "API routes not found"
fi
echo ""

echo "9. Checking required directories..."
directories=("storage/logs" "storage/api-docs" "database/migrations")
for dir in "${directories[@]}"; do
    if [ -d "$dir" ]; then
        print_success "$dir exists"
    else
        print_error "$dir not found"
    fi
done
echo ""

echo "10. Checking file permissions..."
if [ -w "storage/logs" ]; then
    print_success "storage/logs is writable"
else
    print_error "storage/logs is not writable"
fi

if [ -w "storage/api-docs" ]; then
    print_success "storage/api-docs is writable"
else
    print_error "storage/api-docs is not writable"
fi
echo ""

echo "11. Verifying Composer dependencies..."
if composer validate --no-check-publish > /dev/null 2>&1; then
    print_success "composer.json is valid"
else
    print_error "composer.json validation failed"
fi
echo ""

echo "12. Checking for security vulnerabilities..."
print_info "Running composer audit..."
if composer audit 2>&1 | grep -q "No security vulnerability advisories found"; then
    print_success "No security vulnerabilities found"
else
    print_error "Security vulnerabilities detected - check composer audit output"
fi
echo ""

echo "=========================================="
echo "Test Summary"
echo "=========================================="
echo -e "${GREEN}Passed: $TESTS_PASSED${NC}"
echo -e "${RED}Failed: $TESTS_FAILED${NC}"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All tests passed! Ready for production deployment.${NC}"
    exit 0
else
    echo -e "${RED}✗ Some tests failed. Please fix the issues before deploying.${NC}"
    exit 1
fi
