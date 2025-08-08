#!/bin/bash

# Database initialization script for GitHub Smart App
# This script can be run manually if automatic initialization fails

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

# Check if docker-compose is running
if ! docker-compose ps | grep -q "Up"; then
    print_error "Docker Compose is not running. Please start the application first:"
    print_status "docker-compose up -d"
    exit 1
fi

# Get MySQL container name
MYSQL_CONTAINER=$(docker-compose ps -q mysql 2>/dev/null || docker ps --format "{{.Names}}" | grep mysql | head -1)

if [ -z "$MYSQL_CONTAINER" ]; then
    print_error "Could not find MySQL container"
    print_status "Available containers:"
    docker ps --format "{{.Names}}"
    exit 1
fi

print_status "Found MySQL container: $MYSQL_CONTAINER"

# Load environment variables
if [ -f "docker.env" ]; then
    source docker.env
    print_status "Loaded environment variables from docker.env"
else
    print_warning "docker.env not found, using default values"
    MYSQL_ROOT_PASSWORD="github_smart_root_$(date +%s)"
    MYSQL_DATABASE="project_management"
    MYSQL_USER="github_smart_user"
    MYSQL_PASSWORD="github_smart_$(date +%s)"
fi

# Wait for MySQL to be ready
print_status "Waiting for MySQL to be ready..."
mysql_ready=false
max_wait=60
wait_time=0

while [ $wait_time -lt $max_wait ] && [ "$mysql_ready" = false ]; do
    if docker exec -i "${MYSQL_CONTAINER}" mysql -u root -p"${MYSQL_ROOT_PASSWORD}" -e "SELECT 1;" 2>/dev/null >/dev/null; then
        mysql_ready=true
        print_success "MySQL is ready"
    else
        print_status "Waiting for MySQL to be ready... ($((max_wait - wait_time))s remaining)"
        sleep 5
        wait_time=$((wait_time + 5))
    fi
done

if [ "$mysql_ready" = false ]; then
    print_error "MySQL did not become ready in time"
    exit 1
fi

# Check if create_tables.sql exists
if [ ! -f "create_tables.sql" ]; then
    print_error "create_tables.sql not found"
    print_status "Please ensure create_tables.sql is in the current directory"
    exit 1
fi

# Copy SQL file to container
print_status "Copying create_tables.sql to container..."
if docker cp create_tables.sql "${MYSQL_CONTAINER}:/tmp/create_tables.sql" 2>/dev/null; then
    print_success "SQL file copied to container"
else
    print_error "Failed to copy SQL file to container"
    exit 1
fi

# Execute the SQL file
print_status "Executing database initialization..."
if docker exec -i "${MYSQL_CONTAINER}" mysql -u root -p"${MYSQL_ROOT_PASSWORD}" < create_tables.sql 2>/dev/null; then
    print_success "Database initialization completed successfully"
    
    # Verify tables were created
    if docker exec -i "${MYSQL_CONTAINER}" mysql -u root -p"${MYSQL_ROOT_PASSWORD}" project_management -e "SHOW TABLES;" 2>/dev/null | grep -q "gh_issues"; then
        print_success "Database tables verified successfully"
        
        # Show table count
        table_count=$(docker exec -i "${MYSQL_CONTAINER}" mysql -u root -p"${MYSQL_ROOT_PASSWORD}" project_management -e "SHOW TABLES;" 2>/dev/null | wc -l)
        print_success "Found $table_count tables in project_management database"
        
        # Show table names
        print_status "Tables in project_management database:"
        docker exec -i "${MYSQL_CONTAINER}" mysql -u root -p"${MYSQL_ROOT_PASSWORD}" project_management -e "SHOW TABLES;" 2>/dev/null | grep -v "Tables_in"
        
    else
        print_warning "Database tables may not have been created properly"
    fi
else
    print_error "Failed to execute database initialization"
    print_status "You may need to run this manually:"
    print_status "  docker exec -i ${MYSQL_CONTAINER} mysql -u root -p${MYSQL_ROOT_PASSWORD} < create_tables.sql"
    exit 1
fi

print_success "Database initialization completed!"
print_status "You can now access the application at: http://localhost:8081"
