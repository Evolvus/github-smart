# 🐳 Docker Setup for GitHub Smart

This guide will help you run the GitHub Smart application using Docker with PHP 8.1.

## 📋 Prerequisites

- Docker Desktop installed
- Docker Compose installed
- Git (to clone the repository)

## 🚀 Quick Start

### 1. Setup Environment
```bash
# Copy the environment template
cp docker.env .env

# Edit the .env file with your GitHub token
nano .env
```

### 2. Update GitHub Token
Edit the `.env` file and replace `your_github_token_here` with your actual GitHub Personal Access Token.

### 3. Start the Application
```bash
# Run the startup script
./start-docker.sh

# Or manually:
docker-compose up --build -d
```

### 4. Access the Application
- **Web Application**: http://localhost:8080
- **MySQL Database**: localhost:3306

## 🔧 Configuration

### Environment Variables (docker.env)
```bash
# GitHub Configuration
GITHUB_TOKEN=your_actual_github_token
GITHUB_ORG=Syneca

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=root
DB_PASSWORD=Evolvus*123
```

## 📊 Useful Commands

### View Logs
```bash
# All containers
docker-compose logs -f

# Specific container
docker-compose logs -f app
docker-compose logs -f mysql
```

### Container Management
```bash
# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# Rebuild containers
docker-compose up --build -d
```

### Access Container Shell
```bash
# PHP/Apache container
docker-compose exec app bash

# MySQL container
docker-compose exec mysql mysql -u root -p
```

### Database Operations
```bash
# Access MySQL
docker-compose exec mysql mysql -u root -pEvolvus*123 project_management

# Import data
docker-compose exec mysql mysql -u root -pEvolvus*123 project_management < create_tables.sql
```

## 🔍 Troubleshooting

### Port Conflicts
If port 8080 is already in use:
```bash
# Edit docker-compose.yml and change:
ports:
  - "8081:80"  # Use 8081 instead
```

### Permission Issues
```bash
# Fix file permissions
docker-compose exec app chown -R www-data:www-data /var/www/html
```

### Database Connection Issues
```bash
# Check if MySQL is running
docker-compose ps

# Restart MySQL
docker-compose restart mysql
```

## 🗑️ Cleanup

### Remove All Data
```bash
# Stop and remove containers with volumes
docker-compose down -v

# Remove images
docker-compose down --rmi all
```

## 📝 Notes

- The application uses PHP 8.1 with Apache
- MySQL 8.0 is used for the database
- All data is persisted in Docker volumes
- The database will be automatically initialized with the schema

## 🆘 Support

If you encounter issues:
1. Check the logs: `docker-compose logs -f`
2. Ensure Docker Desktop is running
3. Verify ports 8080 and 3306 are available
4. Check that your GitHub token has the required permissions 