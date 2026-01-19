#!/bin/bash

# Quick API Endpoints Testing Script
# Usage: ./scripts/test-api-endpoints.sh [base_url] [api_token]

# Default values
BASE_URL="${1:-http://localhost}"
API_TOKEN="${2}"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo "=========================================="
echo "ADMS HR API - Endpoint Testing"
echo "=========================================="
echo ""
echo "Base URL: $BASE_URL"
echo ""

if [ -z "$API_TOKEN" ]; then
    echo -e "${YELLOW}Warning: No API token provided${NC}"
    echo "Usage: $0 [base_url] [api_token]"
    echo ""
    echo "Testing public endpoints only..."
    echo ""
fi

# Function to test endpoint
test_endpoint() {
    local method=$1
    local endpoint=$2
    local description=$3
    local data=$4
    
    echo -e "${BLUE}Testing:${NC} $description"
    echo "  $method $endpoint"
    
    if [ -z "$API_TOKEN" ]; then
        echo -e "${YELLOW}  ⊘ Skipped (requires token)${NC}"
        echo ""
        return
    fi
    
    if [ "$method" = "GET" ]; then
        response=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL$endpoint" \
            -H "Authorization: Bearer $API_TOKEN" \
            -H "Accept: application/json")
    elif [ "$method" = "POST" ]; then
        response=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL$endpoint" \
            -H "Authorization: Bearer $API_TOKEN" \
            -H "Content-Type: application/json" \
            -H "Accept: application/json" \
            -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | sed '$d')
    
    if [ "$http_code" -ge 200 ] && [ "$http_code" -lt 300 ]; then
        echo -e "${GREEN}  ✓ Success (HTTP $http_code)${NC}"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    else
        echo -e "${RED}  ✗ Failed (HTTP $http_code)${NC}"
        echo "$body" | jq '.' 2>/dev/null || echo "$body"
    fi
    echo ""
}

# Test API Documentation
echo "=========================================="
echo "1. API Documentation"
echo "=========================================="
echo ""

echo -e "${BLUE}Testing:${NC} API Documentation Page"
doc_response=$(curl -s -w "%{http_code}" -o /dev/null "$BASE_URL/api/documentation")
if [ "$doc_response" = "200" ]; then
    echo -e "${GREEN}  ✓ Documentation accessible at $BASE_URL/api/documentation${NC}"
else
    echo -e "${RED}  ✗ Documentation not accessible (HTTP $doc_response)${NC}"
fi
echo ""

# Test HR API Endpoints
echo "=========================================="
echo "2. HR API Endpoints"
echo "=========================================="
echo ""

# Get attendances with date range
test_endpoint "GET" "/api/v1/attendances?start_date=2024-01-01&end_date=2024-12-31" \
    "Get attendances by date range"

# Get attendances with pagination
test_endpoint "GET" "/api/v1/attendances?start_date=2024-01-01&end_date=2024-12-31&per_page=10&page=1" \
    "Get attendances with pagination"

# Get attendances by employee
test_endpoint "GET" "/api/v1/attendances/employee/EMP001?start_date=2024-01-01&end_date=2024-12-31" \
    "Get attendances by employee ID"

# Test Management Endpoints
echo "=========================================="
echo "3. Management Endpoints"
echo "=========================================="
echo ""

# List API tokens
test_endpoint "GET" "/api/v1/admin/tokens" \
    "List all API tokens"

# List webhooks
test_endpoint "GET" "/api/v1/admin/webhooks" \
    "List all webhook configurations"

# Test Authentication
echo "=========================================="
echo "4. Authentication Tests"
echo "=========================================="
echo ""

echo -e "${BLUE}Testing:${NC} Request without token"
response=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/api/v1/attendances?start_date=2024-01-01&end_date=2024-12-31" \
    -H "Accept: application/json")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "401" ]; then
    echo -e "${GREEN}  ✓ Correctly rejected (HTTP 401)${NC}"
else
    echo -e "${RED}  ✗ Unexpected response (HTTP $http_code)${NC}"
fi
echo ""

echo -e "${BLUE}Testing:${NC} Request with invalid token"
response=$(curl -s -w "\n%{http_code}" -X GET "$BASE_URL/api/v1/attendances?start_date=2024-01-01&end_date=2024-12-31" \
    -H "Authorization: Bearer invalid-token-12345" \
    -H "Accept: application/json")
http_code=$(echo "$response" | tail -n1)
if [ "$http_code" = "401" ]; then
    echo -e "${GREEN}  ✓ Correctly rejected (HTTP 401)${NC}"
else
    echo -e "${RED}  ✗ Unexpected response (HTTP $http_code)${NC}"
fi
echo ""

# Summary
echo "=========================================="
echo "Testing Complete"
echo "=========================================="
echo ""

if [ -z "$API_TOKEN" ]; then
    echo -e "${YELLOW}Note: Provide API token to test authenticated endpoints${NC}"
    echo "Usage: $0 $BASE_URL YOUR_API_TOKEN"
else
    echo -e "${GREEN}All tests completed with token${NC}"
fi
echo ""
echo "For detailed API documentation, visit:"
echo "  $BASE_URL/api/documentation"
echo ""
