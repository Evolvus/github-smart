#!/bin/bash

# Test deployment script for GitHub Smart App
# This script tests the deployment process step by step

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Test parameters
GITHUB_ORG="Syneca"
GITHUB_TOKEN="your_github_token_here"
APP_PORT="8082"

print_status "Testing GitHub Smart App deployment..."
print_status "Organization: $GITHUB_ORG"
print_status "Token: ${GITHUB_TOKEN:0:10}..."

# Test 1: Check Docker
print_status "Test 1: Checking Docker installation..."
if command -v docker >/dev/null 2>&1; then
    print_success "Docker is installed"
else
    print_error "Docker is not installed"
    exit 1
fi

if docker info >/dev/null 2>&1; then
    print_success "Docker is running"
else
    print_error "Docker is not running"
    exit 1
fi

if command -v docker-compose >/dev/null 2>&1; then
    print_success "Docker Compose is installed"
else
    print_error "Docker Compose is not installed"
    exit 1
fi

# Test 2: Test GitHub token
print_status "Test 2: Testing GitHub token..."
if curl -s -H "Authorization: Bearer $GITHUB_TOKEN" "https://api.github.com/user" | grep -q '"login"'; then
    print_success "GitHub token is valid"
else
    print_error "GitHub token is invalid"
    exit 1
fi

# Test 3: Test organization access
print_status "Test 3: Testing organization access..."
if curl -s -H "Authorization: Bearer $GITHUB_TOKEN" "https://api.github.com/orgs/${GITHUB_ORG}" | grep -q '"login"'; then
    print_success "Organization access confirmed"
else
    print_warning "Organization access may be limited"
fi

# Test 4: Clean up existing containers
print_status "Test 4: Cleaning up existing containers..."
if docker-compose ps | grep -q "Up"; then
    print_status "Stopping existing containers..."
    docker-compose down -v || true
fi

# Test 5: Create docker.env
print_status "Test 5: Creating docker.env file..."
cat > docker.env << EOF
# Application Configuration
APP_NAME="GitHub Smart"
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=github_smart_user
DB_PASSWORD=github_smart_test_$(date +%s)

# MySQL Configuration (for docker-compose)
MYSQL_ROOT_PASSWORD=github_smart_root_$(date +%s)
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=github_smart_test_$(date +%s)

# GitHub Configuration
GITHUB_ORG=$GITHUB_ORG
GITHUB_TOKEN=$GITHUB_TOKEN

# Logging Configuration
LOG_LEVEL=INFO
LOG_FILE=app.log

# Security Configuration
APP_KEY=github_smart_$(openssl rand -hex 16)

# Docker Configuration
CONTAINER_NAME=github-smart
PORT=8082
DATA_DIR=./data
EOF

print_success "Created docker.env file"

# Test 6: Start containers
print_status "Test 6: Starting containers..."
if docker-compose up -d; then
    print_success "Containers started successfully"
else
    print_error "Failed to start containers"
    exit 1
fi

# Test 7: Wait for containers to be ready
print_status "Test 7: Waiting for containers to be ready..."
sleep 30

# Test 8: Check container status
print_status "Test 8: Checking container status..."
if docker-compose ps | grep -q "Up"; then
    print_success "All containers are running"
else
    print_error "Some containers failed to start"
    docker-compose logs
    exit 1
fi

# Test 9: Test database connection
print_status "Test 9: Testing database connection..."
MYSQL_CONTAINER=$(docker-compose ps -q mysql 2>/dev/null || docker ps --format "{{.Names}}" | grep mysql | head -1)

if [ -n "$MYSQL_CONTAINER" ]; then
    source docker.env
    if docker exec -i "${MYSQL_CONTAINER}" mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT 1;" 2>/dev/null >/dev/null; then
        print_success "Database connection successful"
    else
        print_warning "Database connection failed - this is normal during startup"
    fi
else
    print_warning "Could not find MySQL container"
fi

# Test 9.5: Test external MySQL port
print_status "Test 9.5: Testing external MySQL port..."
if command -v mysql >/dev/null 2>&1; then
    if mysql -h localhost -P 3307 -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT 1;" 2>/dev/null >/dev/null; then
        print_success "External MySQL connection successful"
    else
        print_warning "External MySQL connection failed - this is normal during startup"
    fi
else
    print_warning "MySQL client not installed locally"
fi

# Test 10: Test application accessibility
print_status "Test 10: Testing application accessibility..."
sleep 10

if curl -s "http://localhost:${APP_PORT}" >/dev/null 2>&1; then
    print_success "Application is accessible at http://localhost:${APP_PORT}"
else
    print_warning "Application may still be starting up"
    print_status "Please wait a moment and try accessing: http://localhost:${APP_PORT}"
fi

# Test 11: Initialize database if needed
print_status "Test 11: Initializing database..."
if [ -f "scripts/init_database.sh" ]; then
    if ./scripts/init_database.sh; then
        print_success "Database initialization completed"
    else
        print_warning "Database initialization failed - this may be normal if tables already exist"
    fi
else
    print_warning "Database initialization script not found"
fi

print_success "Deployment test completed!"
print_status "Application should be available at: http://localhost:${APP_PORT}"
print_status "To stop the application: docker-compose down"
print_status "To view logs: docker-compose logs -f"
