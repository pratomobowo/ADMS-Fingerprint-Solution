#!/bin/bash

# Script to verify API documentation setup

echo "=========================================="
echo "ADMS HR API Documentation Verification"
echo "=========================================="
echo ""

# Check if L5-Swagger is installed
echo "1. Checking L5-Swagger installation..."
if php artisan list | grep -q "l5-swagger:generate"; then
    echo "   ✓ L5-Swagger is installed"
else
    echo "   ✗ L5-Swagger is not installed"
    echo "   Run: composer require darkaonline/l5-swagger"
    exit 1
fi

# Check if documentation is generated
echo ""
echo "2. Checking generated documentation..."
if [ -f "storage/api-docs/api-docs.json" ]; then
    echo "   ✓ Documentation file exists"
    
    # Count endpoints
    ENDPOINT_COUNT=$(grep -o '"operationId":' storage/api-docs/api-docs.json | wc -l | tr -d ' ')
    echo "   ✓ Found $ENDPOINT_COUNT documented endpoints"
else
    echo "   ✗ Documentation file not found"
    echo "   Run: php artisan l5-swagger:generate"
    exit 1
fi

# Check if routes are registered
echo ""
echo "3. Checking API routes..."
if php artisan route:list | grep -q "api/documentation"; then
    echo "   ✓ Documentation route is registered"
else
    echo "   ✗ Documentation route not found"
    exit 1
fi

# List all documented endpoints
echo ""
echo "4. Documented endpoints:"
grep -o '"operationId": "[^"]*"' storage/api-docs/api-docs.json | sed 's/"operationId": "//g' | sed 's/"//g' | while read -r op; do
    echo "   - $op"
done

# List all schemas
echo ""
echo "5. Documented schemas:"
grep -o '"#/components/schemas/[^"]*"' storage/api-docs/api-docs.json | sed 's/"#\/components\/schemas\///g' | sed 's/"//g' | sort -u | while read -r schema; do
    echo "   - $schema"
done

echo ""
echo "=========================================="
echo "✓ API Documentation is properly configured!"
echo "=========================================="
echo ""
echo "Access the documentation at:"
echo "http://your-domain.com/api/documentation"
echo ""
echo "To regenerate documentation:"
echo "php artisan l5-swagger:generate"
echo ""
