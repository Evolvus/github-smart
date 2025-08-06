# Troubleshooting Guide

## Port Conflicts

### Issue: Port 3306 is already in use

This happens when MySQL is already running on your system or another Docker container is using port 3306.

#### Solution 1: Use different ports (Recommended)
```bash
# Edit docker-compose.yml to use different ports
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  app:
    image: ghcr.io/evolvus/github-smart:latest
    ports:
      - "8080:8080"  # Changed from 80:8080
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
      - "3307:3306"  # Changed from 3306:3306
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
```

#### Solution 2: Stop existing MySQL service
```bash
# Stop local MySQL service
sudo systemctl stop mysql
# or
sudo service mysql stop

# Or stop Docker containers using port 3306
docker ps | grep 3306
docker stop <container_id>
```

#### Solution 3: Check what's using the port
```bash
# Check what's using port 3306
sudo lsof -i :3306
# or
netstat -tulpn | grep 3306
```

### Issue: Port 80 is already in use

#### Solution: Use different port
```bash
# Change the app port in docker-compose.yml
ports:
  - "8080:8080"  # Instead of "80:8080"
```

## Other Common Issues

### Issue: Permission denied
```bash
# Fix permissions
sudo chown -R $USER:$USER /opt/github-smart
```

### Issue: Docker not running
```bash
# Start Docker
sudo systemctl start docker
# or
sudo service docker start
```

### Issue: Not enough disk space
```bash
# Clean up Docker
docker system prune -a
```

### Issue: Memory issues
```bash
# Check available memory
free -h

# Increase Docker memory limit in Docker Desktop settings
```

## Quick Fix Commands

```bash
# Stop all containers and remove volumes
docker-compose down -v

# Remove all unused containers, networks, images
docker system prune -a

# Restart Docker
sudo systemctl restart docker

# Try deployment again
./deploy-production.sh
```

## Accessing the Application

After fixing port conflicts:
- **App**: http://localhost:8080 (if you changed from port 80)
- **MySQL**: localhost:3307 (if you changed from port 3306)

## Database Connection

If you changed the MySQL port, update your database connection:
```bash
# Connect to MySQL on the new port
mysql -h localhost -P 3307 -u root -p
``` 