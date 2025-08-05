#!/bin/bash

# Docker setup and troubleshooting script for GitHub Smart

set -e

echo "🐳 GitHub Smart Docker Setup Script"
echo "=================================="

# Function to cleanup Docker resources
cleanup() {
    echo "🧹 Cleaning up Docker resources..."
    docker-compose down -v
    docker system prune -f
    echo "✅ Cleanup completed"
}

# Function to rebuild the application
rebuild() {
    echo "🔨 Rebuilding Docker containers..."
    docker-compose build --no-cache
    docker-compose up -d
    echo "✅ Rebuild completed"
}

# Function to check container status
status() {
    echo "📊 Container status:"
    docker-compose ps
    echo ""
    echo "📋 Container logs:"
    docker-compose logs --tail=20
}

# Function to verify vendor directory
verify_vendor() {
    echo "🔍 Verifying vendor directory..."
    docker-compose exec app ls -la /var/www/html/vendor
    echo ""
    echo "🧪 Testing autoloader..."
    docker-compose exec app php -r "require_once '/var/www/html/vendor/autoload.php'; echo '✅ Autoloader working correctly';"
}

# Function to install dependencies in container
install_deps() {
    echo "📦 Installing dependencies in container..."
    docker-compose exec app composer install --no-dev --optimize-autoloader
    echo "✅ Dependencies installed"
}

# Main script logic
case "${1:-help}" in
    "cleanup")
        cleanup
        ;;
    "rebuild")
        cleanup
        rebuild
        ;;
    "status")
        status
        ;;
    "verify")
        verify_vendor
        ;;
    "install")
        install_deps
        ;;
    "full-setup")
        echo "🚀 Full setup process..."
        cleanup
        rebuild
        echo "⏳ Waiting for containers to start..."
        sleep 10
        status
        verify_vendor
        echo "🎉 Setup completed! Access the application at http://localhost:8081"
        ;;
    *)
        echo "Usage: $0 {cleanup|rebuild|status|verify|install|full-setup}"
        echo ""
        echo "Commands:"
        echo "  cleanup     - Clean up Docker resources"
        echo "  rebuild     - Rebuild containers from scratch"
        echo "  status      - Show container status and logs"
        echo "  verify      - Verify vendor directory and autoloader"
        echo "  install     - Install dependencies in running container"
        echo "  full-setup  - Complete setup process (recommended for first time)"
        echo ""
        echo "For first-time setup, run: $0 full-setup"
        ;;
esac 