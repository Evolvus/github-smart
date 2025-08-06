#!/bin/bash

# Production Deployment Script for GitHub Smart
set -e

echo "🚀 GitHub Smart Production Deployment"
echo "===================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker Desktop."
        exit 1
    fi
    print_status "Docker is running"
}

# Function to check if required ports are available
check_ports() {
    local ports=("8081" "3306")
    for port in "${ports[@]}"; do
        if lsof -Pi :$port -sTCP:LISTEN -t >/dev/null 2>&1; then
            print_warning "Port $port is already in use. Please stop the service using this port."
            exit 1
        fi
    done
    print_status "Required ports are available"
}

# Function to setup environment
setup_environment() {
    if [ ! -f "docker.env" ]; then
        print_status "Creating docker.env from template..."
        cp docker.env.example docker.env
        print_warning "Please edit docker.env with your production settings before continuing."
        print_warning "Required settings:"
        print_warning "  - GITHUB_TOKEN: Your GitHub Personal Access Token"
        print_warning "  - GITHUB_ORG: Your GitHub organization name"
        print_warning "  - MYSQL_ROOT_PASSWORD: Secure MySQL root password"
        print_warning "  - MYSQL_PASSWORD: Secure MySQL user password"
        print_warning "  - APP_KEY: Random 32-character string"
        echo ""
        read -p "Press Enter after editing docker.env to continue..."
    else
        print_status "docker.env already exists"
    fi
}

# Function to validate environment
validate_environment() {
    print_status "Validating environment configuration..."
    
    # Check if docker.env exists
    if [ ! -f "docker.env" ]; then
        print_error "docker.env not found. Please run setup first."
        exit 1
    fi
    
    # Source environment variables
    source docker.env
    
    # Validate required variables
    local required_vars=("GITHUB_TOKEN" "GITHUB_ORG" "MYSQL_ROOT_PASSWORD" "MYSQL_PASSWORD")
    for var in "${required_vars[@]}"; do
        if [ -z "${!var}" ] || [ "${!var}" = "your_*_here" ]; then
            print_error "Please set $var in docker.env"
            exit 1
        fi
    done
    
    print_status "Environment validation passed"
}

# Function to build and deploy
deploy() {
    print_status "Building and deploying application..."
    
    # Stop existing containers
    docker-compose down 2>/dev/null || true
    
    # Build and start containers
    docker-compose up --build -d
    
    # Wait for services to be healthy
    print_status "Waiting for services to be ready..."
    local max_attempts=30
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        if docker-compose ps | grep -q "healthy"; then
            print_status "All services are healthy"
            break
        fi
        
        if [ $attempt -eq $max_attempts ]; then
            print_error "Services failed to become healthy within expected time"
            docker-compose logs
            exit 1
        fi
        
        echo -n "."
        sleep 2
        ((attempt++))
    done
    
    print_status "Deployment completed successfully!"
}

# Function to show status
show_status() {
    print_status "Container status:"
    docker-compose ps
    
    echo ""
    print_status "Application URLs:"
    echo "  Web Application: http://localhost:8081"
    echo "  MySQL Database: localhost:3306"
    
    echo ""
    print_status "Useful commands:"
    echo "  View logs: docker-compose logs -f"
    echo "  Stop application: docker-compose down"
    echo "  Restart application: docker-compose restart"
}

# Function to show logs
show_logs() {
    print_status "Showing recent logs..."
    docker-compose logs --tail=50
}

# Main script logic
case "${1:-deploy}" in
    "check")
        check_docker
        check_ports
        setup_environment
        validate_environment
        print_status "Environment check completed successfully"
        ;;
    "setup")
        check_docker
        setup_environment
        print_status "Setup completed. Please edit docker.env and run 'deploy'"
        ;;
    "deploy")
        check_docker
        check_ports
        validate_environment
        deploy
        show_status
        ;;
    "status")
        show_status
        ;;
    "logs")
        show_logs
        ;;
    "stop")
        print_status "Stopping application..."
        docker-compose down
        print_status "Application stopped"
        ;;
    "restart")
        print_status "Restarting application..."
        docker-compose restart
        print_status "Application restarted"
        ;;
    "cleanup")
        print_status "Cleaning up Docker resources..."
        docker-compose down -v
        docker system prune -f
        print_status "Cleanup completed"
        ;;
    *)
        echo "Usage: $0 {check|setup|deploy|status|logs|stop|restart|cleanup}"
        echo ""
        echo "Commands:"
        echo "  check     - Check environment and dependencies"
        echo "  setup     - Setup environment files"
        echo "  deploy    - Deploy the application (default)"
        echo "  status    - Show application status"
        echo "  logs      - Show application logs"
        echo "  stop      - Stop the application"
        echo "  restart   - Restart the application"
        echo "  cleanup   - Clean up Docker resources"
        echo ""
        echo "For first-time deployment:"
        echo "  1. $0 setup"
        echo "  2. Edit docker.env with your settings"
        echo "  3. $0 deploy"
        ;;
esac 