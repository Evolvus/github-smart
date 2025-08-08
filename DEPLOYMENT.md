# GitHub Smart App - Deployment Guide

This guide provides comprehensive instructions for deploying the GitHub Smart App using Docker.

## Quick Deployment

For automatic deployment with your GitHub organization and token:

```bash
rm -rf * && curl -fsSL https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh | bash -s -- -o Syneca -t YOUR_GITHUB_TOKEN
```

## Prerequisites

1. **Docker**: Install Docker Desktop or Docker Engine
2. **Docker Compose**: Usually included with Docker Desktop
3. **GitHub Token**: Personal Access Token with `repo` and `read:org` scopes
4. **GitHub Organization**: Your GitHub organization or username

## Manual Deployment Steps

### 1. Clone or Download the Repository

```bash
git clone https://github.com/Evolvus/github-smart.git
cd github-smart
```

### 2. Run the Deployment Script

```bash
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN
```

Or set environment variables:

```bash
export GITHUB_ORG="your-organization"
export GITHUB_TOKEN="your-github-token"
./deploy.sh
```

### 3. Verify Deployment

The script will:
- Check Docker installation
- Validate GitHub token
- Pull Docker images
- Start containers with MySQL
- Initialize database tables
- Verify application accessibility

## Database Setup

The application uses MySQL 8.0 with automatic table creation. The database setup includes:

- **gh_issues**: GitHub issues data
- **gh_projects**: GitHub projects
- **gh_issue_tags**: Issue tags and labels
- **gh_pinned_issues**: Pinned issues for users
- **gh_audit**: API operation tracking
- **expense_perm_matrix**: Permission matrix
- **crux_auth**: Authentication data

### Automatic Database Initialization

The deployment script automatically:
1. Waits for MySQL container to be ready
2. Creates database tables using `create_tables.sql`
3. Verifies table creation
4. Provides fallback initialization if needed

### Manual Database Setup

If automatic setup fails, run:

```bash
./scripts/init_database.sh
```

## Container Architecture

The application runs with two containers:

### App Container
- **Image**: `ghcr.io/evolvus/github-smart:latest`
- **Port**: 8081 (external) → 8080 (internal)
- **Environment**: PHP 8.1, Apache, Application code

### MySQL Container
- **Image**: `mysql:8.0`
- **Port**: 3307 (external) → 3306 (internal)
- **Database**: `project_management`
- **User**: `github_smart_user`
- **Volume**: Persistent MySQL data

## Configuration

### Environment Variables

The deployment creates a `docker.env` file with:

```bash
# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=github_smart_user
DB_PASSWORD=auto_generated

# MySQL Configuration
MYSQL_ROOT_PASSWORD=auto_generated
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=auto_generated

# External MySQL Port: 3307

# GitHub Configuration
GITHUB_ORG=your_organization
GITHUB_TOKEN=your_token

# Application Configuration
APP_ENV=production
APP_DEBUG=false
```

### Docker Compose

The `docker-compose.yml` includes:
- Health checks for both containers
- Automatic database initialization
- Volume persistence
- Environment variable injection

## Troubleshooting

### Common Issues

#### 1. Docker Not Running
```bash
# Start Docker Desktop or Docker Engine
docker info
```

#### 2. Port Already in Use
```bash
# Check what's using port 8081
lsof -i :8081

# Use different port
./deploy.sh -o ORG -t TOKEN -p 8082
```

#### 3. Database Connection Issues
```bash
# Check MySQL container
docker-compose ps mysql

# View MySQL logs
docker-compose logs mysql

# Manual database setup
./scripts/init_database.sh
```

#### 4. GitHub Token Issues
```bash
# Test token manually
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.github.com/user

# Ensure token has required scopes:
# - repo (for repository access)
# - read:org (for organization access)
```

#### 5. Application Not Accessible
```bash
# Check container status
docker-compose ps

# View application logs
docker-compose logs app

# Restart containers
docker-compose restart
```

### Debugging Commands

```bash
# View all container logs
docker-compose logs -f

# Access MySQL container
docker-compose exec mysql mysql -u root -p

# Access application container
docker-compose exec app bash

# Check database tables
docker-compose exec mysql mysql -u root -p project_management -e "SHOW TABLES;"
```

### Testing Deployment

Run the test script to verify deployment:

```bash
./test_deployment.sh
```

This script will:
1. Check Docker installation
2. Validate GitHub token
3. Test organization access
4. Start containers
5. Verify database setup
6. Test application accessibility

## Management Commands

### Start Application
```bash
docker-compose up -d
```

### Stop Application
```bash
docker-compose down
```

### Restart Application
```bash
docker-compose restart
```

### View Logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f mysql
```

### Update Application
```bash
# Pull latest image
docker-compose pull

# Restart with new image
docker-compose up -d
```

### Backup Database
```bash
docker-compose exec mysql mysqldump -u root -p project_management > backup.sql
```

### Restore Database
```bash
docker-compose exec -T mysql mysql -u root -p project_management < backup.sql
```

## Security Considerations

1. **GitHub Token**: Store securely, rotate regularly
2. **Database Passwords**: Auto-generated, stored in `docker.env`
3. **Port Exposure**: Only port 8081 exposed externally
4. **Container Isolation**: Each service runs in separate container

## Performance Optimization

### Resource Limits
Add to `docker-compose.yml`:
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
          memory: 512M
          cpus: '0.25'
```

### Database Optimization
```sql
-- Add indexes for better performance
ALTER TABLE gh_issues ADD INDEX idx_created_at (created_at);
ALTER TABLE gh_issues ADD INDEX idx_updated_at (updated_at);
```

## Monitoring

### Health Checks
- Application: HTTP endpoint check
- MySQL: Database connectivity check

### Log Monitoring
```bash
# Monitor application logs
docker-compose logs -f app | grep ERROR

# Monitor database logs
docker-compose logs -f mysql | grep ERROR
```

## Support

For issues with deployment:
1. Check the troubleshooting section above
2. Run `./test_deployment.sh` for diagnostics
3. View container logs for error details
4. Ensure all prerequisites are met

## Version History

- **v2.1**: Improved database initialization, better error handling
- **v2.0**: Docker Compose integration, automatic setup
- **v1.0**: Initial deployment script 