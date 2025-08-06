#!/bin/bash

# GitHub Smart App Deployment Script
# This script downloads and sets up the GitHub Smart application using Docker

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
DOCKER_IMAGE=""
CONTAINER_NAME="github-smart"
PORT="8080"
DATA_DIR="./data"
BUILD_LOCAL=false

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

    print_success "Docker is installed and running"
}

# Function to get user input
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

    if [ -z "$DOCKER_IMAGE" ]; then
        DOCKER_IMAGE="ghcr.io/evolvus/github-smart:latest"
    fi
}

# Function to build image locally
build_image_locally() {
    print_status "Building Docker image locally..."
    if docker build -t "$DOCKER_IMAGE" .; then
        print_success "Docker image built successfully"
        return 0
    else
        print_error "Failed to build Docker image"
        exit 1
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

    if [ -z "$DOCKER_IMAGE" ]; then
        print_error "Docker image is required"
        exit 1
    fi
}

# Function to create data directory
create_data_dir() {
    if [ ! -d "$DATA_DIR" ]; then
        print_status "Creating data directory: $DATA_DIR"
        mkdir -p "$DATA_DIR"
    fi
}

# Function to stop and remove existing container
cleanup_existing() {
    if docker ps -a --format "table {{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
        print_status "Stopping existing container: $CONTAINER_NAME"
        docker stop "$CONTAINER_NAME" || true
        docker rm "$CONTAINER_NAME" || true
    fi
}

# Function to pull Docker image
pull_image() {
    # Check if image exists locally first
    if docker images --format "table {{.Repository}}:{{.Tag}}" | grep -q "^${DOCKER_IMAGE}$"; then
        print_status "Using local Docker image: $DOCKER_IMAGE"
        return 0
    fi
    
    # If build-local flag is set, build locally
    if [ "$BUILD_LOCAL" = true ]; then
        print_status "Building Docker image locally as requested..."
        build_image_locally
        return 0
    fi
    
    # Only try to pull from registry if image doesn't exist locally
    print_status "Local image not found, attempting to pull from GitHub Container Registry..."
    print_status "Logging in to GitHub Container Registry..."
    if echo "$GITHUB_TOKEN" | docker login ghcr.io -u "$GITHUB_ORG" --password-stdin; then
        print_status "Pulling Docker image: $DOCKER_IMAGE"
        if docker pull "$DOCKER_IMAGE"; then
            print_success "Docker image pulled successfully"
        else
            print_warning "Failed to pull image from registry"
            print_status "Would you like to build the image locally? (y/n)"
            read -r response
            if [[ "$response" =~ ^[Yy]$ ]]; then
                build_image_locally
            else
                print_error "Cannot proceed without Docker image"
                exit 1
            fi
        fi
    else
        print_error "Failed to login to GitHub Container Registry"
        print_status "Would you like to build the image locally? (y/n)"
        read -r response
        if [[ "$response" =~ ^[Yy]$ ]]; then
            build_image_locally
        else
            print_error "Cannot proceed without Docker image"
            exit 1
        fi
    fi
}

# Function to run container
run_container() {
    print_status "Starting container: $CONTAINER_NAME"
    
    docker run -d \
        --name "$CONTAINER_NAME" \
        --restart unless-stopped \
        -p "$PORT:8080" \
        -v "$(pwd)/$DATA_DIR:/var/www/html/data" \
        -e GITHUB_ORG="$GITHUB_ORG" \
        -e GITHUB_TOKEN="$GITHUB_TOKEN" \
        "$DOCKER_IMAGE"

    if [ $? -eq 0 ]; then
        print_success "Container started successfully"
    else
        print_error "Failed to start container"
        exit 1
    fi
}

# Function to check container status
check_status() {
    print_status "Checking container status..."
    sleep 5
    
    if docker ps --format "table {{.Names}}" | grep -q "^${CONTAINER_NAME}$"; then
        print_success "Container is running"
        print_status "Application is available at: http://localhost:$PORT"
        print_status "Container logs: docker logs $CONTAINER_NAME"
        print_status "Stop container: docker stop $CONTAINER_NAME"
    else
        print_error "Container failed to start"
        docker logs "$CONTAINER_NAME" || true
        exit 1
    fi
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo
    echo "Options:"
    echo "  -o, --org ORGANIZATION    GitHub organization/username"
    echo "  -t, --token TOKEN         GitHub Personal Access Token"
    echo "  -i, --image IMAGE         Docker image (default: ghcr.io/evolvus/github-smart:latest)"
    echo "  -p, --port PORT           Port to expose (default: 8080)"
    echo "  -n, --name NAME           Container name (default: github-smart)"
    echo "  -d, --data-dir DIR        Data directory (default: ./data)"
    echo "  -b, --build-local         Build Docker image locally instead of pulling"
    echo "  -h, --help                Show this help message"
    echo
    echo "Environment variables:"
    echo "  GITHUB_ORG               GitHub organization/username"
    echo "  GITHUB_TOKEN             GitHub Personal Access Token"
    echo "  DOCKER_IMAGE             Docker image"
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
        -i|--image)
            DOCKER_IMAGE="$2"
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
        -b|--build-local)
            BUILD_LOCAL=true
            shift
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
    
    # Create data directory
    create_data_dir
    
    # Cleanup existing container
    cleanup_existing
    
    # Pull Docker image
    pull_image
    
    # Run container
    run_container
    
    # Check status
    check_status
    
    print_success "Deployment completed successfully!"
    print_status "You can now access the application at: http://localhost:$PORT"
}

# Run main function
main "$@" 