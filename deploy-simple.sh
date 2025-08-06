#!/bin/bash

# GitHub Smart Simple Deployment Script
# This script deploys to the current directory

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DOCKER_IMAGE="ghcr.io/evolvus/github-smart:latest"

echo -e "${GREEN}🚀 GitHub Smart Simple Deployment${NC}"
echo "=================================="

# Check prerequisites
echo -e "${YELLOW}📋 Checking prerequisites...${NC}"

# Check Docker
if ! command -v docker &> /dev/null; then
    echo -e "${RED}❌ Docker is not installed${NC}"
    exit 1
fi

# Check Docker Compose
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}❌ Docker Compose is not installed${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Prerequisites check passed${NC}"

# Check for port conflicts
echo -e "${YELLOW}🔍 Checking for port conflicts...${NC}"
MYSQL_PORT=3306
APP_PORT=8080

# Check if MySQL port is in use
if lsof -Pi :$MYSQL_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Port $MYSQL_PORT is in use, switching to 3307${NC}"
    MYSQL_PORT=3307
fi

# Check if app port is in use
if lsof -Pi :$APP_PORT -sTCP:LISTEN -t >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️  Port $APP_PORT is in use, switching to 8081${NC}"
    APP_PORT=8081
fi

# Generate secure passwords
echo -e "${YELLOW}🔐 Generating secure passwords...${NC}"
DB_PASSWORD=$(openssl rand -base64 32)
ROOT_PASSWORD=$(openssl rand -base64 32)
APP_KEY=$(openssl rand -base64 32)

# Create environment file
echo -e "${YELLOW}⚙️  Creating environment configuration...${NC}"
cat > .env << EOF
# Application Configuration
APP_NAME=GitHub Smart
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=github_smart_user
DB_PASSWORD=$DB_PASSWORD

# MySQL Configuration
MYSQL_ROOT_PASSWORD=$ROOT_PASSWORD
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=$DB_PASSWORD

# GitHub Configuration
GITHUB_TOKEN=${GITHUB_TOKEN:-YOUR_GITHUB_PERSONAL_ACCESS_TOKEN}
GITHUB_ORG=${GITHUB_ORG:-YOUR_ORGANIZATION_NAME}

# Logging Configuration
LOG_LEVEL=INFO
LOG_FILE=app.log

# Security Configuration
APP_KEY=$APP_KEY
EOF

# Create docker-compose.yml with dynamic ports
echo -e "${YELLOW}🐳 Creating Docker Compose configuration...${NC}"
cat > docker-compose.yml << EOF
version: '3.8'

services:
  app:
    image: ghcr.io/evolvus/github-smart:latest
    ports:
      - "$APP_PORT:8080"
    depends_on:
      mysql:
        condition: service_healthy
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html/public
      - APP_ENV=production
      - APP_DEBUG=false
    env_file:
      - .env
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:8080/"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    ports:
      - "$MYSQL_PORT:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

volumes:
  mysql_data:
EOF

# Pull Docker image
echo -e "${YELLOW}📦 Pulling Docker image...${NC}"
docker pull $DOCKER_IMAGE

# Start services
echo -e "${YELLOW}🚀 Starting services...${NC}"
docker-compose up -d

# Wait for services to be healthy
echo -e "${YELLOW}⏳ Waiting for services to be healthy...${NC}"
sleep 30

# Check service status
echo -e "${YELLOW}📊 Checking service status...${NC}"
docker-compose ps

# Check if GitHub token is provided
if [ "$GITHUB_TOKEN" = "YOUR_GITHUB_PERSONAL_ACCESS_TOKEN" ] || [ -z "$GITHUB_TOKEN" ]; then
    echo -e "${YELLOW}⚠️  Warning: GitHub token not provided${NC}"
    echo "You can set it using environment variables:"
    echo "export GITHUB_TOKEN=your_github_token"
    echo "export GITHUB_ORG=your_organization"
    echo ""
    echo "Or update the .env file manually after deployment."
fi

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo -e "${YELLOW}📝 Next steps:${NC}"
if [ "$GITHUB_TOKEN" != "YOUR_GITHUB_PERSONAL_ACCESS_TOKEN" ] && [ -n "$GITHUB_TOKEN" ]; then
    echo "✅ GitHub token and organization configured via environment variables"
else
    echo "1. Set environment variables or update the .env file:"
    echo "   export GITHUB_TOKEN=your_actual_token"
    echo "   export GITHUB_ORG=your_organization"
    echo "   docker-compose restart"
fi
echo ""
echo "2. Access your application at: http://localhost:$APP_PORT"
echo "   MySQL is available at localhost:$MYSQL_PORT"
echo ""
echo -e "${YELLOW}📋 Useful commands:${NC}"
echo "- Check status: docker-compose ps"
echo "- View logs: docker-compose logs -f"
echo "- Stop: docker-compose down"
echo ""
echo -e "${GREEN}🎉 GitHub Smart is now deployed!${NC}" 