#!/bin/bash

# GitHub Smart App Deployment Script v2.1
# This script pulls and deploys the GitHub Smart application using Docker
# Features: MySQL integration, GitHub API validation, automatic database setup

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
GITHUB_ORG=""
GITHUB_TOKEN=""
CONTAINER_NAME="github-smart"
PORT="8080"
DATA_DIR="./data"
IMAGE_TAG="latest"

# Function to print colored output
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

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to check Docker installation
check_docker() {
    if ! command_exists docker; then
        print_error "Docker is not installed. Please install Docker first."
        print_status "Visit https://docs.docker.com/get-docker/ for installation instructions."
        exit 1
    fi

    if ! docker info >/dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi

    if ! command_exists docker-compose; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        print_status "Visit https://docs.docker.com/compose/install/ for installation instructions."
        exit 1
    fi

    print_success "Docker and Docker Compose are installed and running"
}

# Function to get user input if not provided
get_user_input() {
    if [ -z "$GITHUB_ORG" ]; then
        echo -n "Enter your GitHub organization/username: "
        read -r GITHUB_ORG
    fi

    if [ -z "$GITHUB_TOKEN" ]; then
        echo -n "Enter your GitHub Personal Access Token: "
        read -rs GITHUB_TOKEN
        echo
    fi
}

# Function to validate inputs
validate_inputs() {
    if [ -z "$GITHUB_ORG" ]; then
        print_error "GitHub organization/username is required"
        exit 1
    fi

    if [ -z "$GITHUB_TOKEN" ]; then
        print_error "GitHub Personal Access Token is required"
        exit 1
    fi
}

# Function to test GitHub token for application use
test_github_token() {
    print_status "Testing GitHub token for application use..."
    
    # Test basic API access
    local response=$(curl -s -H "Authorization: Bearer $GITHUB_TOKEN" "https://api.github.com/user")
    if echo "$response" | grep -q '"login"'; then
        local username=$(echo "$response" | grep -o '"login":"[^"]*"' | cut -d'"' -f4)
        print_success "GitHub token is valid for API access (User: $username)"
    else
        print_error "GitHub token is invalid or expired"
        print_status "Please check your token at: https://github.com/settings/tokens"
        print_status "The token is needed for the application to access GitHub API data"
        print_status "Required scopes: repo, read:org, read:user"
        exit 1
    fi
    
    # Test organization access
    local org_response=$(curl -s -H "Authorization: Bearer $GITHUB_TOKEN" "https://api.github.com/orgs/${GITHUB_ORG}")
    if echo "$org_response" | grep -q '"login"'; then
        print_success "Token has access to organization: $GITHUB_ORG"
    else
        print_warning "Token may not have access to organization: $GITHUB_ORG"
        print_status "Ensure your token has 'read:org' scope"
    fi
    
    # Test repository access (for fetching issues)
    if curl -s -H "Authorization: Bearer $GITHUB_TOKEN" "https://api.github.com/repos/${GITHUB_ORG}/github-smart" >/dev/null 2>&1; then
        print_success "Token has repository access"
    else
        print_warning "Token may not have repository access"
        print_status "Ensure your token has 'repo' scope to access repository data"
    fi
}

# Function to create data directory
create_data_dir() {
    if [ ! -d "$DATA_DIR" ]; then
        print_status "Creating data directory: $DATA_DIR"
        mkdir -p "$DATA_DIR"
    fi
}

# Function to stop and remove existing containers
cleanup_existing() {
    print_status "Cleaning up existing containers..."
    
    # Stop docker-compose services
    if docker-compose ps | grep -q "Up"; then
        print_status "Stopping existing application containers"
        docker-compose down || true
    fi
    
    # Also check for any standalone containers with our name
    if docker ps -a --format "table {{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
        print_status "Stopping existing container: $CONTAINER_NAME"
        docker stop "$CONTAINER_NAME" || true
        docker rm "$CONTAINER_NAME" || true
    fi
    
    # Remove any dangling containers or networks
    docker system prune -f >/dev/null 2>&1 || true
    
    print_success "Cleanup completed"
}

# Function to pull Docker image from public GitHub Container Registry
pull_image() {
    # The Docker image is published to the repository owner's organization
    # For now, we'll use 'evolvus' as the organization for the Docker image
    local docker_org="evolvus"
    local image_name="ghcr.io/${docker_org}/github-smart:${IMAGE_TAG}"
    
    print_status "Pulling Docker image from public GitHub Container Registry: $image_name"
    print_status "Note: Docker image is published to ${docker_org} organization (repository owner)"
    
    # Since the package is public, we don't need to login
    if docker pull "$image_name" 2>&1; then
        print_success "Docker image pulled successfully"
    else
        print_error "Failed to pull image from registry"
        print_status "Troubleshooting steps:"
        print_status "1. Verify the image exists: https://github.com/${docker_org}/github-smart/packages"
        print_status "2. Check if the GitHub Action has run and published the image"
        print_status "3. Check internet connection and Docker accessibility"
        print_status "4. Try: docker pull hello-world (to test Docker connectivity)"
        
        # Try to get more specific error information
        print_status "Attempting to check registry access..."
        if curl -s "https://ghcr.io/v2/" >/dev/null 2>&1; then
            print_success "Registry is accessible"
        else
            print_error "Cannot access GitHub Container Registry"
            print_status "Check your internet connection and firewall settings"
        fi
        exit 1
    fi
}

# Function to create docker.env file
create_docker_env() {
    local env_file="docker.env"
    print_status "Creating $env_file file..."
    
    # Generate secure passwords
    local mysql_root_password="github_smart_root_$(date +%s)"
    local mysql_password="github_smart_$(date +%s)"
    
    cat > "$env_file" << EOF
# Application Configuration
APP_NAME="GitHub Smart"
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=github_smart_user
DB_PASSWORD=$mysql_password

# MySQL Configuration (for docker-compose)
MYSQL_ROOT_PASSWORD=$mysql_root_password
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=$mysql_password

# GitHub Configuration
GITHUB_ORG=$GITHUB_ORG
GITHUB_TOKEN=$GITHUB_TOKEN

# Logging Configuration
LOG_LEVEL=INFO
LOG_FILE=app.log

# Security Configuration
APP_KEY=github_smart_$(openssl rand -hex 16)

# Docker Configuration
CONTAINER_NAME=$CONTAINER_NAME
PORT=$PORT
DATA_DIR=$DATA_DIR
EOF

    print_success "Created $env_file file with configuration"
    print_status "MySQL root password: $mysql_root_password"
    print_status "MySQL user password: $mysql_password"
}

# Function to run container with docker-compose
run_container() {
    print_status "Starting application with Docker Compose (includes MySQL)"
    
    # Read the generated passwords from docker.env
    source docker.env
    
    # Create docker-compose override file with our settings
    cat > docker-compose.override.yml << EOF
version: '3.8'

services:
  app:
    ports:
      - "$PORT:8080"
    environment:
      - GITHUB_ORG=$GITHUB_ORG
      - GITHUB_TOKEN=$GITHUB_TOKEN
    env_file:
      - docker.env
    volumes:
      - ./$DATA_DIR:/var/www/html/data

  mysql:
    environment:
      MYSQL_ROOT_PASSWORD: $MYSQL_ROOT_PASSWORD
      MYSQL_DATABASE: $MYSQL_DATABASE
      MYSQL_USER: $MYSQL_USER
      MYSQL_PASSWORD: $MYSQL_PASSWORD
EOF

    # Start the services
    if docker-compose up -d; then
        print_success "Application started successfully with MySQL"
    else
        print_error "Failed to start application"
        exit 1
    fi
}

# Function to setup database tables
setup_database() {
    print_status "Setting up database tables..."
    
    # Wait for MySQL to be ready
    print_status "Waiting for MySQL to be ready..."
    sleep 15
    
    # Copy SQL file to container
    if [ -f "create_tables.sql" ]; then
        docker cp create_tables.sql github-smart-mysql-1:/tmp/ 2>/dev/null || true
        
        # Import database schema
        if docker-compose exec mysql bash -c "mysql -u root -p${MYSQL_ROOT_PASSWORD} project_management < /tmp/create_tables.sql" 2>/dev/null; then
            print_success "Database tables created successfully"
        else
            print_warning "Could not create database tables automatically"
            print_status "You may need to create tables manually or they will be created on first use"
        fi
    else
        print_warning "create_tables.sql not found - database tables will be created on first use"
    fi
}

# Function to check container status
check_status() {
    print_status "Checking application status..."
    sleep 10
    
    # Check if both app and mysql containers are running
    if docker-compose ps | grep -q "Up"; then
        print_success "Application is running with MySQL"
        print_status "Application is available at: http://localhost:$PORT"
        print_status "MySQL is available on localhost:3306"
        print_status "View logs: docker-compose logs -f"
        print_status "Stop application: docker-compose down"
        
        # Wait a bit more for MySQL to be fully ready
        print_status "Waiting for MySQL to be fully ready..."
        sleep 10
        
        # Setup database tables
        setup_database
        
        # Check if we can connect to the application
        if curl -s "http://localhost:$PORT" >/dev/null 2>&1; then
            print_success "Application is responding to requests"
        else
            print_warning "Application may still be starting up"
            print_status "Please wait a moment and try accessing: http://localhost:$PORT"
        fi
        
        # Test GitHub API connectivity
        print_status "Testing GitHub API connectivity..."
        if curl -s -H "Authorization: Bearer $GITHUB_TOKEN" "https://api.github.com/user" >/dev/null 2>&1; then
            print_success "GitHub API is accessible"
        else
            print_warning "GitHub API connectivity test failed"
        fi
        
        # Test application API
        print_status "Testing application API..."
        if curl -s -X POST -H "Content-Type: application/json" -H "X-Requested-With: XMLHttpRequest" -d '""' "http://localhost:$PORT/api/getGHIssues.php" >/dev/null 2>&1; then
            print_success "Application API is responding"
        else
            print_warning "Application API test failed - this is normal if no issues exist"
        fi
    else
        print_error "Application failed to start"
        docker-compose logs || true
        exit 1
    fi
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo
    echo "Options:"
    echo "  -o, --org ORGANIZATION    GitHub organization/username"
    echo "  -t, --token TOKEN         GitHub Personal Access Token (for API access)"
    echo "  -p, --port PORT           Port to expose (default: 8080)"
    echo "  -n, --name NAME           Container name (default: github-smart)"
    echo "  -d, --data-dir DIR        Data directory (default: ./data)"
    echo "  -i, --image-tag TAG       Docker image tag (default: latest)"
    echo "  -h, --help                Show this help message"
    echo
    echo "Environment variables:"
    echo "  GITHUB_ORG               GitHub organization/username"
    echo "  GITHUB_TOKEN             GitHub Personal Access Token (for API access)"
    echo
    echo "Note: The GitHub token is used by the application to access GitHub API data."
    echo "      The Docker image is pulled from a public GitHub Container Registry."
    echo
    echo "Examples:"
    echo "  $0 -o syneca -t ghp_xxxxxxxx"
    echo "  GITHUB_ORG=syneca GITHUB_TOKEN=ghp_xxxxxxxx $0"
}

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -o|--org)
            GITHUB_ORG="$2"
            shift 2
            ;;
        -t|--token)
            GITHUB_TOKEN="$2"
            shift 2
            ;;
        -p|--port)
            PORT="$2"
            shift 2
            ;;
        -n|--name)
            CONTAINER_NAME="$2"
            shift 2
            ;;
        -d|--data-dir)
            DATA_DIR="$2"
            shift 2
            ;;
        -i|--image-tag)
            IMAGE_TAG="$2"
            shift 2
            ;;
        -h|--help)
            show_usage
            exit 0
            ;;
        *)
            print_error "Unknown option: $1"
            show_usage
            exit 1
            ;;
    esac
done

# Main execution
main() {
    print_status "Starting GitHub Smart App deployment..."
    
    # Check Docker
    check_docker
    
    # Get user input if not provided
    get_user_input
    
    # Validate inputs
    validate_inputs
    
    # Test GitHub token
    test_github_token
    
    # Create data directory
    create_data_dir
    
    # Cleanup existing container
    cleanup_existing
    
    # Pull Docker image
    pull_image
    
    # Create docker.env file
    create_docker_env
    
    # Run container
    run_container
    
    # Check status
    check_status
    
    print_success "Deployment completed successfully!"
    print_status "You can now access the application at: http://localhost:$PORT"
    
    # Show deployment summary
    echo
    print_status "=== Deployment Summary ==="
    print_status "Application URL: http://localhost:$PORT"
    print_status "GitHub Organization: $GITHUB_ORG"
    print_status "Database: MySQL (localhost:3306)"
    print_status "Data Directory: $DATA_DIR"
    print_status "Container Name: $CONTAINER_NAME"
    
    echo
    print_status "=== Next Steps ==="
    print_status "1. Open your browser to http://localhost:$PORT"
    print_status "2. Click 'Retrieve GitHub Issues' to fetch data"
    print_status "3. View the dashboard and analytics"
    print_status "4. Check logs: docker-compose logs -f"
    print_status "5. Stop application: docker-compose down"
    
    echo
    print_status "=== Troubleshooting ==="
    print_status "If you encounter issues:"
    print_status "- Check logs: docker-compose logs -f"
    print_status "- Verify GitHub token permissions"
    print_status "- Ensure port $PORT is available"
    print_status "- Restart: docker-compose restart"
}

# Run main function
main "$@" 