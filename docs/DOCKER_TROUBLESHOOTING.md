# Docker Troubleshooting Guide

## Common Issues and Solutions

### 1. Vendor Directory / Autoloader Issues

**Problem**: 
```
Warning: require(/var/www/html/vendor/composer/../myclabs/deep-copy/src/DeepCopy/deep_copy.php): Failed to open stream: No such file or directory
```

**Root Cause**: 
The vendor directory is being overwritten by the volume mount in docker-compose.yml. When you mount the entire project directory, it overwrites the vendor directory that was installed during the Docker build.

**Solution**: 
We've implemented a named volume for the vendor directory to prevent this issue:

```yaml
volumes:
  - .:/var/www/html
  - vendor_data:/var/www/html/vendor
```

**Quick Fix**:
```bash
# Use the automated setup script
./scripts/docker-setup.sh full-setup
```

### 2. Container Build Issues

**Problem**: Build fails or containers don't start properly

**Solution**:
```bash
# Clean everything and rebuild
./scripts/docker-setup.sh cleanup
./scripts/docker-setup.sh rebuild
```

### 3. Permission Issues

**Problem**: Apache can't access files or write logs

**Solution**:
```bash
# Rebuild with proper permissions
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### 4. Database Connection Issues

**Problem**: Application can't connect to MySQL

**Solution**:
```bash
# Check if MySQL container is running
docker-compose ps

# Check MySQL logs
docker-compose logs mysql

# Restart MySQL container
docker-compose restart mysql
```

## Setup Scripts

### Automated Setup Script

The `scripts/docker-setup.sh` script provides several commands:

```bash
# Full setup (recommended for first time)
./scripts/docker-setup.sh full-setup

# Individual commands
./scripts/docker-setup.sh cleanup     # Clean Docker resources
./scripts/docker-setup.sh rebuild     # Rebuild containers
./scripts/docker-setup.sh status      # Show container status
./scripts/docker-setup.sh verify      # Verify vendor directory
./scripts/docker-setup.sh install     # Install dependencies
```

### Manual Troubleshooting Steps

1. **Stop all containers**:
   ```bash
   docker-compose down -v
   ```

2. **Clean Docker system**:
   ```bash
   docker system prune -f
   ```

3. **Rebuild without cache**:
   ```bash
   docker-compose build --no-cache
   ```

4. **Start containers**:
   ```bash
   docker-compose up -d
   ```

5. **Verify vendor directory**:
   ```bash
   docker-compose exec app ls -la /var/www/html/vendor
   ```

6. **Test autoloader**:
   ```bash
   docker-compose exec app php -r "require_once '/var/www/html/vendor/autoload.php'; echo 'Autoloader working';"
   ```

## Volume Management

### Named Volumes

The application uses named volumes to prevent issues:

- `mysql_data`: MySQL database data
- `vendor_data`: Composer vendor directory

### Volume Cleanup

To completely reset the application:

```bash
# Remove all volumes
docker-compose down -v
docker volume rm github-smart_vendor_data github-smart_mysql_data

# Rebuild everything
./scripts/docker-setup.sh full-setup
```

## Environment Configuration

### Required Environment Variables

Make sure your `docker.env` file contains:

```env
# Database
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=root
DB_PASSWORD=your_password

# GitHub
GITHUB_TOKEN=your_github_token
GITHUB_ORG=your_organization

# Application
APP_ENV=development
APP_DEBUG=true
```

### Environment File Setup

```bash
# Copy example file
cp docker.env.example docker.env

# Edit with your values
nano docker.env
```

## Logs and Debugging

### View Container Logs

```bash
# All containers
docker-compose logs

# Specific container
docker-compose logs app
docker-compose logs mysql

# Follow logs in real-time
docker-compose logs -f app
```

### Debug Container

```bash
# Access container shell
docker-compose exec app bash

# Check PHP configuration
docker-compose exec app php -m

# Check Composer
docker-compose exec app composer --version
```

## Performance Optimization

### Build Optimization

The Dockerfile includes several optimizations:

1. **Multi-stage caching**: Composer files are copied first
2. **Optimized autoloader**: `--optimize-autoloader` flag
3. **No-dev dependencies**: Production-only packages
4. **Dockerignore**: Excludes unnecessary files

### Volume Optimization

The `.dockerignore` file excludes:
- Git files
- Documentation
- Test files
- IDE files
- Log files

This reduces build context size and improves build speed.

## Security Considerations

### File Permissions

The Dockerfile sets proper permissions:
```dockerfile
RUN chown -R www-data:www-data /var/www/html
```

### Environment Variables

Never commit sensitive data:
- Use `.env` files (not in version control)
- Use Docker secrets for production
- Rotate tokens regularly

## Production Deployment

### Production Dockerfile

For production, consider:
- Using multi-stage builds
- Implementing health checks
- Setting up proper logging
- Using production PHP configuration

### Health Checks

Add to docker-compose.yml:
```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/health"]
  interval: 30s
  timeout: 10s
  retries: 3
```

## Common Commands Reference

```bash
# Start application
docker-compose up -d

# Stop application
docker-compose down

# View logs
docker-compose logs -f

# Rebuild
docker-compose build --no-cache

# Access container
docker-compose exec app bash

# Check status
docker-compose ps

# Clean everything
./scripts/docker-setup.sh cleanup
``` 