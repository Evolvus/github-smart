# Production Deployment Guide

This guide covers deploying GitHub Smart in production using Docker containers.

## 🚀 Quick Start

### Option 1: Using Pre-built Docker Image (Recommended)

1. **Download deployment files**
   ```bash
   # Download the latest release artifacts from GitHub
   # Or use the deployment script to fetch from GitHub Container Registry
   ```

2. **Setup environment**
   ```bash
   # Copy the environment template
   cp docker.env.example docker.env
   
   # Edit with your production settings
   nano docker.env
   ```

3. **Deploy**
   ```bash
   # Run the deployment script
   ./deploy-production.sh deploy
   ```

### Option 2: Build from Source

1. **Clone and setup**
   ```bash
   git clone <repository-url>
   cd github-smart
   ./scripts/deploy-production.sh setup
   ```

2. **Configure environment**
   ```bash
   # Edit docker.env with your settings
   nano docker.env
   ```

3. **Deploy**
   ```bash
   ./scripts/deploy-production.sh deploy
   ```

## 📋 Prerequisites

### System Requirements
- **Docker**: 20.10 or higher
- **Docker Compose**: 2.0 or higher
- **Memory**: Minimum 2GB RAM
- **Storage**: At least 5GB free space
- **Ports**: 8081 (web), 3306 (database)

### GitHub Setup
1. **Create GitHub Personal Access Token**
   - Go to GitHub Settings → Developer settings → Personal access tokens
   - Generate new token with scopes:
     - `repo` (for private repositories)
     - `read:org` (for organization access)
     - `read:user` (for user information)
     - `read:project` (for project access)

2. **Note your organization name**
   - The GitHub organization you want to manage issues for

## ⚙️ Configuration

### Environment Variables

Edit `docker.env` with your production settings:

```env
# Application Configuration
APP_NAME=GitHub Smart
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=github_smart_user
DB_PASSWORD=your_secure_password_here

# MySQL Configuration
MYSQL_ROOT_PASSWORD=your_secure_root_password_here
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=your_secure_password_here

# GitHub Configuration
GITHUB_TOKEN=your_github_token_here
GITHUB_ORG=your_organization_name

# Logging Configuration
LOG_LEVEL=INFO
LOG_FILE=app.log

# Security Configuration
APP_KEY=your_random_32_character_string_here
```

### Security Best Practices

1. **Use strong passwords**
   - Generate random passwords for database users
   - Use different passwords for root and application users

2. **Secure GitHub token**
   - Use minimal required permissions
   - Rotate tokens regularly
   - Store tokens securely

3. **Generate APP_KEY**
   ```bash
   # Generate a random 32-character string
   openssl rand -base64 24
   ```

## 🐳 Docker Deployment

### Using the Deployment Script

The deployment script provides several commands:

```bash
# Check environment and dependencies
./scripts/deploy-production.sh check

# Setup environment files
./scripts/deploy-production.sh setup

# Deploy the application
./scripts/deploy-production.sh deploy

# Check application status
./scripts/deploy-production.sh status

# View application logs
./scripts/deploy-production.sh logs

# Stop the application
./scripts/deploy-production.sh stop

# Restart the application
./scripts/deploy-production.sh restart

# Clean up Docker resources
./scripts/deploy-production.sh cleanup
```

### Manual Docker Commands

If you prefer manual deployment:

```bash
# Build and start containers
docker-compose up --build -d

# View logs
docker-compose logs -f

# Stop containers
docker-compose down

# Restart containers
docker-compose restart
```

## 🔧 Production Configuration

### Database Configuration

The application uses MySQL 8.0 with the following setup:

- **Database**: `project_management`
- **User**: `github_smart_user` (application user)
- **Root**: Full database access
- **Persistence**: Data stored in Docker volume `mysql_data`

### Web Server Configuration

- **Port**: 8081 (configurable)
- **Document Root**: `/var/www/html/public`
- **Apache**: Configured with security headers
- **Health Checks**: Automatic health monitoring

### Security Features

- **Non-root user**: Application runs as `appuser`
- **Security headers**: Apache configured with security headers
- **Input validation**: All inputs sanitized
- **SQL injection protection**: Prepared statements
- **XSS protection**: HTML escaping

## 📊 Monitoring

### Health Checks

The application includes health checks:

```bash
# Check container health
docker-compose ps

# View health check logs
docker-compose logs app | grep health
```

### Logs

```bash
# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f mysql
```

### Application Logs

Application logs are stored in the container at `/var/www/html/logs/app.log`:

```bash
# View application logs
docker-compose exec app tail -f /var/www/html/logs/app.log
```

## 🔄 Updates and Maintenance

### Updating the Application

1. **Pull latest changes**
   ```bash
   git pull origin main
   ```

2. **Rebuild and restart**
   ```bash
   docker-compose down
   docker-compose up --build -d
   ```

### Database Backups

```bash
# Create backup
docker-compose exec mysql mysqldump -u root -p project_management > backup.sql

# Restore backup
docker-compose exec -T mysql mysql -u root -p project_management < backup.sql
```

### Log Rotation

Configure log rotation to prevent disk space issues:

```bash
# Add to your system's logrotate configuration
/var/lib/docker/volumes/*/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 root root
}
```

## 🚨 Troubleshooting

### Common Issues

1. **Port conflicts**
   ```bash
   # Check what's using the ports
   lsof -i :8081
   lsof -i :3306
   ```

2. **Database connection issues**
   ```bash
   # Check database container
   docker-compose logs mysql
   
   # Test database connection
   docker-compose exec app php -r "echo 'Database connection test';"
   ```

3. **GitHub API issues**
   - Verify token permissions
   - Check rate limits
   - Validate organization name

4. **Memory issues**
   ```bash
   # Check container resource usage
   docker stats
   ```

### Debug Mode

For troubleshooting, temporarily enable debug mode:

```env
APP_DEBUG=true
LOG_LEVEL=DEBUG
```

### Getting Help

1. Check the logs: `./scripts/deploy-production.sh logs`
2. Verify environment: `./scripts/deploy-production.sh check`
3. Review GitHub token permissions
4. Check Docker and system resources

## 🔒 Security Checklist

- [ ] Strong database passwords
- [ ] Secure GitHub token with minimal permissions
- [ ] APP_KEY set to random string
- [ ] APP_DEBUG=false in production
- [ ] Firewall configured (if applicable)
- [ ] Regular security updates
- [ ] Database backups configured
- [ ] Log monitoring enabled

## 📈 Performance Optimization

### Resource Limits

Add resource limits to `docker-compose.yml`:

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G
          cpus: '0.5'
  mysql:
    deploy:
      resources:
        limits:
          memory: 2G
          cpus: '1.0'
```

### Caching

Consider adding Redis for caching:

```yaml
services:
  redis:
    image: redis:7-alpine
    restart: unless-stopped
```

### Load Balancing

For high availability, use multiple instances behind a load balancer.

## 🎯 Production Checklist

Before going live:

- [ ] Environment variables configured
- [ ] Database passwords changed from defaults
- [ ] GitHub token with correct permissions
- [ ] Health checks passing
- [ ] Logs being written correctly
- [ ] Backup strategy implemented
- [ ] Monitoring configured
- [ ] Security audit completed
- [ ] Performance testing done
- [ ] Documentation updated 