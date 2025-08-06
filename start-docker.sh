#!/bin/bash

echo "🐳 Starting GitHub Smart with Docker..."
echo ""

# Check if .env file exists, if not copy from docker.env
if [ ! -f .env ]; then
    echo "📝 Creating .env file from docker.env..."
    cp docker.env .env
fi

# Build and start the containers
echo "🚀 Building and starting containers..."
docker-compose up --build -d

# Wait for MySQL to be ready
echo "⏳ Waiting for MySQL to be ready..."
sleep 30

# Check if containers are running
echo "🔍 Checking container status..."
docker-compose ps

echo ""
echo "✅ Application should be running at: http://localhost:8081"
echo "📊 MySQL is available at: localhost:3306"
echo ""
echo "📋 Useful commands:"
echo "  - View logs: docker-compose logs -f"
echo "  - Stop: docker-compose down"
echo "  - Restart: docker-compose restart"
echo "  - Shell access: docker-compose exec app bash" 