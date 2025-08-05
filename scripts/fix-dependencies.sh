#!/bin/bash

# Fix Dependencies Script for GitHub Smart
# This script ensures all dependencies are properly installed

echo "🔧 Fixing dependencies..."

# Check if we're in a Docker container
if [ -f /.dockerenv ]; then
    echo "🐳 Running inside Docker container..."
    
    # Install/update composer dependencies
    composer install --no-interaction
    
    # Generate optimized autoloader
    composer dump-autoload --optimize
    
    # Fix permissions
    chown -R www-data:www-data /var/www/html
    
    echo "✅ Dependencies fixed in Docker container"
else
    echo "🖥️  Running on host system..."
    
    # Check if Docker is running
    if docker-compose ps | grep -q "Up"; then
        echo "🐳 Fixing dependencies in Docker containers..."
        docker-compose exec app composer install --no-interaction
        docker-compose exec app composer dump-autoload --optimize
        docker-compose exec app chown -R www-data:www-data /var/www/html
        echo "✅ Dependencies fixed in Docker containers"
    else
        echo "❌ Docker containers are not running. Please start them first:"
        echo "   docker-compose up -d"
        exit 1
    fi
fi

echo "🎉 Dependency fix completed!" 