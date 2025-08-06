# GitHub Smart - Production Setup Guide

This guide provides step-by-step instructions for deploying the GitHub Smart application in production using the GitHub Container Registry package.

## Prerequisites

- Docker and Docker Compose installed on your production server
- GitHub Personal Access Token with appropriate permissions
- Domain name (optional, for SSL setup)
- Server with at least 2GB RAM and 10GB storage

## Step 1: Server Preparation

### 1.1 Create Application Directory
```bash
# Create production directory
mkdir -p /opt/github-smart
cd /opt/github-smart
```

### 1.2 Set Up Environment Variables
```bash
# Create environment file
cat > .env << 'EOF'
# Application Configuration
APP_NAME=GitHub Smart
APP_ENV=production
APP_DEBUG=false

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=github_smart_user
DB_PASSWORD=YOUR_SECURE_DB_PASSWORD_HERE

# MySQL Configuration
MYSQL_ROOT_PASSWORD=YOUR_SECURE_ROOT_PASSWORD_HERE
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=YOUR_SECURE_DB_PASSWORD_HERE

# GitHub Configuration
GITHUB_TOKEN=YOUR_GITHUB_PERSONAL_ACCESS_TOKEN
GITHUB_ORG=YOUR_ORGANIZATION_NAME

# Logging Configuration
LOG_LEVEL=INFO
LOG_FILE=app.log

# Security Configuration
APP_KEY=YOUR_RANDOM_32_CHARACTER_STRING_HERE
EOF
```

### 1.3 Create Docker Compose Configuration
```bash
# Create docker-compose.yml
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  app:
    image: ghcr.io/evolvus/github-smart:latest
    ports:
      - "80:8080"
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
    volumes:
      - app_logs:/var/log/apache2
      - app_data:/var/www/html/storage

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
      MYSQL_DATABASE: ${MYSQL_DATABASE}
      MYSQL_USER: ${MYSQL_USER}
      MYSQL_PASSWORD: ${MYSQL_PASSWORD}
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
      - mysql_logs:/var/log/mysql
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

  nginx:
    image: nginx:alpine
    ports:
      - "443:443"
      - "80:80"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app
    restart: unless-stopped

volumes:
  mysql_data:
  mysql_logs:
  app_logs:
  app_data:
EOF
```

## Step 2: Security Configuration

### 2.1 Generate Secure Passwords
```bash
# Generate secure passwords
DB_PASSWORD=$(openssl rand -base64 32)
ROOT_PASSWORD=$(openssl rand -base64 32)
APP_KEY=$(openssl rand -base64 32)

# Update .env file with generated passwords
sed -i "s/YOUR_SECURE_DB_PASSWORD_HERE/$DB_PASSWORD/g" .env
sed -i "s/YOUR_SECURE_ROOT_PASSWORD_HERE/$ROOT_PASSWORD/g" .env
sed -i "s/YOUR_RANDOM_32_CHARACTER_STRING_HERE/$APP_KEY/g" .env
```

### 2.2 Set Up SSL (Optional but Recommended)
```bash
# Create SSL directory
mkdir -p ssl

# Generate self-signed certificate (replace with your domain)
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout ssl/nginx.key -out ssl/nginx.crt \
  -subj "/C=US/ST=State/L=City/O=Organization/CN=your-domain.com"
```

### 2.3 Create Nginx Configuration
```bash
# Create nginx.conf
cat > nginx.conf << 'EOF'
events {
    worker_connections 1024;
}

http {
    upstream app {
        server app:8080;
    }

    server {
        listen 80;
        server_name your-domain.com;
        return 301 https://$server_name$request_uri;
    }

    server {
        listen 443 ssl;
        server_name your-domain.com;

        ssl_certificate /etc/nginx/ssl/nginx.crt;
        ssl_certificate_key /etc/nginx/ssl/nginx.key;

        location / {
            proxy_pass http://app;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
}
EOF
```

## Step 3: GitHub Token Setup

### 3.1 Create GitHub Personal Access Token
1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Generate new token with these permissions:
   - `repo` (Full control of private repositories)
   - `read:org` (Read organization data)
   - `read:user` (Read user data)

### 3.2 Update Environment File
```bash
# Replace with your actual GitHub token and organization
sed -i "s/YOUR_GITHUB_PERSONAL_ACCESS_TOKEN/your_actual_token/g" .env
sed -i "s/YOUR_ORGANIZATION_NAME/your_organization/g" .env
```

## Step 4: Deployment

### 4.1 Pull the Latest Image
```bash
# Pull the latest image
docker pull ghcr.io/evolvus/github-smart:latest
```

### 4.2 Start the Application
```bash
# Start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f
```

### 4.3 Verify Deployment
```bash
# Check if containers are healthy
docker-compose ps

# Test the application
curl -I http://localhost

# Check database connection
docker-compose exec mysql mysql -u root -p -e "SHOW DATABASES;"
```

## Step 5: Monitoring and Maintenance

### 5.1 Set Up Log Rotation
```bash
# Create logrotate configuration
cat > /etc/logrotate.d/github-smart << 'EOF'
/opt/github-smart/logs/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 root root
}
EOF
```

### 5.2 Create Backup Script
```bash
# Create backup script
cat > backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/backups/github-smart"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR

# Backup database
docker-compose exec mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD project_management > $BACKUP_DIR/db_backup_$DATE.sql

# Backup application data
docker-compose exec app tar -czf - /var/www/html/storage > $BACKUP_DIR/app_data_$DATE.tar.gz

# Keep only last 7 days of backups
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
EOF

chmod +x backup.sh
```

### 5.3 Set Up Cron Jobs
```bash
# Add to crontab
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/github-smart/backup.sh") | crontab -
```

## Step 6: Updates and Maintenance

### 6.1 Update Application
```bash
# Pull latest image
docker pull ghcr.io/evolvus/github-smart:latest

# Restart services
docker-compose down
docker-compose up -d

# Check logs
docker-compose logs -f
```

### 6.2 Database Migrations
```bash
# If database schema changes are needed
docker-compose exec app php /var/www/html/scripts/setup_database.php
```

## Troubleshooting

### Common Issues

1. **Container won't start**
   ```bash
   docker-compose logs [service_name]
   ```

2. **Database connection issues**
   ```bash
   docker-compose exec mysql mysql -u root -p
   ```

3. **Application errors**
   ```bash
   docker-compose logs app
   ```

4. **Permission issues**
   ```bash
   sudo chown -R 1000:1000 /opt/github-smart
   ```

### Health Checks
```bash
# Check all services
docker-compose ps

# Check specific service
docker-compose exec app curl -f http://localhost:8080/

# Check database
docker-compose exec mysql mysqladmin ping -h localhost
```

## Security Checklist

- [ ] Strong passwords generated and used
- [ ] SSL certificate configured
- [ ] Firewall rules configured
- [ ] Regular backups scheduled
- [ ] Log monitoring set up
- [ ] GitHub token has minimal required permissions
- [ ] Environment variables secured
- [ ] Container images updated regularly

## Performance Optimization

1. **Database Optimization**
   ```bash
   # Add to MySQL configuration
   docker-compose exec mysql mysql -u root -p -e "
   SET GLOBAL innodb_buffer_pool_size = 1073741824;
   SET GLOBAL max_connections = 200;
   "
   ```

2. **Application Caching**
   - Configure Redis for session storage
   - Enable application-level caching

3. **Monitoring**
   - Set up Prometheus/Grafana for metrics
   - Configure alerting for critical issues

## Support

For issues and support:
- Check application logs: `docker-compose logs -f`
- Review GitHub issues: [Repository Issues]
- Contact support team: [Support Email] 