# Production Migration Guide

This document explains the migration from the old development-focused Docker setup to the new production-ready approach.

## 🔄 What Changed

### Old Approach (Development-Focused)
- Required local `composer install` before Docker build
- Used volume mounts for development flexibility
- Mixed development and production concerns
- Manual dependency management
- No health checks or security hardening

### New Approach (Production-Ready)
- Multi-stage Docker builds with built-in dependency installation
- No local composer installation required
- Production-optimized with security hardening
- Automated health checks and monitoring
- GitHub Package Registry integration
- Standalone deployment packages

## 🚀 Key Improvements

### 1. **Multi-stage Docker Builds**
```dockerfile
# Old approach: Required local vendor directory
COPY . .

# New approach: Multi-stage build
FROM composer:2.6 as composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

FROM php:8.1-apache
COPY --from=composer /app/vendor ./vendor
```

### 2. **Security Enhancements**
- **Non-root user**: Application runs as `appuser` instead of root
- **Security headers**: Apache configured with security headers
- **Health checks**: Built-in health monitoring
- **Production defaults**: `APP_DEBUG=false`, `APP_ENV=production`

### 3. **Automated Deployment**
- **GitHub Actions**: Automated builds and publishing to GitHub Container Registry
- **Deployment scripts**: Automated deployment and management
- **Environment validation**: Automatic validation of required settings

### 4. **Production Packages**
- **Standalone packages**: Users can download pre-built deployment packages
- **Self-contained**: Everything needed for deployment in one package
- **Versioned releases**: Proper versioning and release management

## 📦 Deployment Options

### Option 1: GitHub Package Registry (Recommended)
```bash
# Pull the latest image
docker pull ghcr.io/your-org/github-smart:latest

# Use with docker-compose
docker-compose up -d
```

### Option 2: Download Deployment Package
```bash
# Download and extract package
wget https://github.com/your-org/github-smart/releases/latest/download/github-smart-deployment-2.0.0.tar.gz
tar -xzf github-smart-deployment-2.0.0.tar.gz
cd github-smart-deployment-2.0.0

# Setup and deploy
./setup.sh
./deploy-production.sh deploy
```

### Option 3: Build from Source
```bash
# Clone and build
git clone https://github.com/your-org/github-smart.git
cd github-smart
./scripts/deploy-production.sh deploy
```

## 🔧 Configuration Changes

### Environment Variables

**Old approach:**
```env
APP_ENV=development
APP_DEBUG=true
DB_USER=root
DB_PASSWORD=Evolvus*123
```

**New approach:**
```env
APP_ENV=production
APP_DEBUG=false
DB_USER=github_smart_user
DB_PASSWORD=your_secure_password_here
APP_KEY=your_random_32_character_string_here
```

### Docker Compose Changes

**Old approach:**
```yaml
volumes:
  - .:/var/www/html
  - vendor_data:/var/www/html/vendor
```

**New approach:**
```yaml
# No volume mounts - everything built into image
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/"]
  interval: 30s
  timeout: 10s
  retries: 3
```

## 🛠️ Migration Steps

### For Existing Users

1. **Backup your data**
   ```bash
   # Backup database
   docker-compose exec mysql mysqldump -u root -p project_management > backup.sql
   
   # Backup environment files
   cp docker.env docker.env.backup
   ```

2. **Stop old containers**
   ```bash
   docker-compose down
   ```

3. **Update to new approach**
   ```bash
   # Pull latest changes
   git pull origin main
   
   # Update environment
   cp docker.env.example docker.env
   # Edit docker.env with your settings
   
   # Deploy with new approach
   ./scripts/deploy-production.sh deploy
   ```

4. **Restore data**
   ```bash
   # Restore database
   docker-compose exec -T mysql mysql -u root -p project_management < backup.sql
   ```

### For New Users

1. **Choose deployment method**
   - GitHub Package Registry (easiest)
   - Download deployment package
   - Build from source

2. **Configure environment**
   ```bash
   cp docker.env.example docker.env
   nano docker.env
   ```

3. **Deploy**
   ```bash
   ./scripts/deploy-production.sh deploy
   ```

## 🔍 What's Removed

### Files Removed
- `start-docker.sh` - Replaced by `deploy-production.sh`
- `setup-env.sh` - Replaced by deployment script
- `scripts/docker-setup.sh` - Replaced by `deploy-production.sh`
- `scripts/fix-dependencies.sh` - No longer needed

### Commands Removed
```bash
# Old commands (no longer needed)
./start-docker.sh
./setup-env.sh
./scripts/docker-setup.sh full-setup
./scripts/fix-dependencies.sh
```

### New Commands
```bash
# New commands
./scripts/deploy-production.sh deploy
./scripts/deploy-production.sh status
./scripts/deploy-production.sh logs
./scripts/deploy-production.sh stop
./scripts/deploy-production.sh restart
```

## 📊 Benefits

### For Developers
- **Simplified setup**: No local composer installation required
- **Consistent builds**: Multi-stage builds ensure consistency
- **Better testing**: Production-like environment for testing
- **Automated releases**: GitHub Actions handle builds and releases

### For Users
- **Easier deployment**: One-command deployment
- **Better security**: Production-hardened containers
- **Health monitoring**: Built-in health checks
- **Standalone packages**: Download and deploy without source code

### For Operations
- **Resource efficiency**: Optimized Docker images
- **Security**: Non-root user, security headers
- **Monitoring**: Health checks and logging
- **Scalability**: Ready for container orchestration

## 🚨 Breaking Changes

1. **Environment variables**: Some variable names changed
2. **Port configuration**: Default port remains 8081
3. **Database user**: Now uses dedicated user instead of root
4. **Volume mounts**: No longer mounts local files for development

## 🔮 Future Enhancements

1. **Kubernetes support**: Helm charts for Kubernetes deployment
2. **Monitoring integration**: Prometheus/Grafana integration
3. **Auto-scaling**: Horizontal pod autoscaling
4. **Backup automation**: Automated database backups
5. **SSL/TLS**: Built-in HTTPS support

## 📚 Additional Resources

- [Production Deployment Guide](PRODUCTION_DEPLOYMENT.md)
- [Security Documentation](SECURITY.md)
- [API Documentation](API.md)
- [GitHub Actions Workflow](../.github/workflows/docker-publish.yml) 