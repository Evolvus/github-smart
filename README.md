# GitHub Smart

A PHP application for managing and visualizing GitHub issues and projects with advanced filtering, analytics, and team collaboration features.

## Features

- 🔗 **GitHub Integration**: Real-time sync with GitHub issues and projects
- 📊 **Advanced Analytics**: Comprehensive dashboards with charts and metrics
- 🔍 **Smart Filtering**: Filter by assignee, tags, projects, status, and more
- 📋 **Project Management**: Organize issues by projects and custom buckets
- 👥 **Team Management**: Role-based access and assignee tracking
- ⚡ **Real-time Updates**: Live data refresh and notifications

## Deployment Options

### 🐳 Production Deployment (Docker)

**Recommended for production environments**

#### Prerequisites
- Docker 20.10+ and Docker Compose
- GitHub Personal Access Token (optional, for GitHub integration)

#### Quick Start
```bash
# Download and run deployment script
curl -O https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh
chmod +x deploy.sh

# Deploy with GitHub integration
./deploy.sh -o your_org -t ghp_your_token_here

# Deploy without GitHub integration
./deploy.sh

# Deploy with custom port
./deploy.sh -o your_org -t ghp_your_token_here -p 9090
```

#### Command Line Options
```bash
./deploy.sh [OPTIONS]

Options:
  -o, --org ORGANIZATION    GitHub organization name
  -t, --token TOKEN         GitHub personal access token
  -p, --port PORT           Application port (default: 8081)
  -h, --help                Show help message

Examples:
  ./deploy.sh -o your_org -t ghp_xxxxxxxxxxxxxxxxxxxx
  ./deploy.sh --org your_org --token ghp_xxxxxxxxxxxxxxxxxxxx --port 9090
  ./deploy.sh  # Basic deployment without GitHub integration
```

#### What the Script Does
1. **Pulls Docker image** from GitHub Container Registry
2. **Starts MySQL container** with proper configuration
3. **Initializes database** with required tables
4. **Verifies database setup** (7+ tables created)
5. **Starts application container** (Nginx + PHP-FPM)
6. **Tests GitHub API** (if token provided)
7. **Imports GitHub issues** (if token provided)
8. **Provides access URLs** and status

#### Access URLs
- **Application**: http://localhost:8081 (or custom port)
- **Database**: localhost:3308 (MySQL)

#### GitHub Token Setup
1. Go to [GitHub Settings → Developer settings → Personal access tokens](https://github.com/settings/tokens)
2. Generate new token with scopes: `repo`, `read:org`, `read:user`
3. Use token in deployment: `./deploy.sh -o YOUR_ORG -t YOUR_TOKEN`

---

### 🛠️ Development Deployment (Local PHP)

**Recommended for development and testing**

#### Prerequisites
- PHP 8.0+
- MySQL 5.7+
- Composer 2.0+
- Web server (Apache/Nginx) or PHP built-in server

#### Setup Instructions

1. **Clone Repository**
```bash
git clone https://github.com/Evolvus/github-smart.git
cd github-smart
```

2. **Install Dependencies**
```bash
composer install
```

3. **Configure Environment**
```bash
# Copy environment template
cp .env.example .env

# Edit configuration
nano .env
```

4. **Environment Configuration (.env)**
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=project_management
DB_USER=your_db_user
DB_PASSWORD=your_db_password

# GitHub API Configuration
GITHUB_TOKEN=your_github_token_here
GITHUB_ORG=your_organization_name

# Application Configuration
APP_ENV=development
DEBUGGER=yes
```

5. **Setup Database**
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE project_management;"

# Import schema
mysql -u root -p project_management < create_tables.sql
```

6. **Start Development Server**
```bash
# Using PHP built-in server
php -S localhost:8000 -t public/

# Or configure your web server to point to the 'public' directory
```

7. **Access Application**
- **URL**: http://localhost:8000
- **Database**: localhost:3306

#### Development Workflow
```bash
# Start development server
php -S localhost:8000 -t public/

# In another terminal, watch for changes
composer dump-autoload

# Test GitHub integration
curl -X POST http://localhost:8000/api/getGHIssues.php
```

---

## API Endpoints

### GitHub Integration
- `POST /api/getGHIssues.php` - Sync GitHub issues
- `GET /api/getGHDash.php` - Dashboard data
- `GET /api/getProjects.php` - List projects

### Project Management
- `POST /api/add_bucket.php` - Create bucket
- `DELETE /api/delete_bucket.php` - Delete bucket
- `PUT /api/update_bucket_name.php` - Update bucket

### Issue Management
- `POST /api/pin_issue.php` - Pin issue
- `PUT /api/update_issue_bucket.php` - Move issue to bucket

---

## Database Schema

The application creates the following tables:
- `gh_issues` - GitHub issues data
- `gh_projects` - GitHub projects
- `gh_issue_tags` - Issue tags/labels
- `gh_pinned_issues` - Pinned issues
- `expense_perm_matrix` - Permission matrix
- `crux_auth` - Authentication rules
- `gh_audit` - Audit logs

---

## Troubleshooting

### Production (Docker)
```bash
# Check container status
docker ps

# View application logs
docker logs github-smart-app

# View database logs
docker logs github-smart-mysql

# Access database
docker exec -it github-smart-mysql mysql -u root -p project_management

# Restart containers
docker restart github-smart-app github-smart-mysql
```

### Development (Local PHP)
```bash
# Check PHP version
php -v

# Check Composer dependencies
composer install

# Verify database connection
php scripts/setup_database.php

# Check application logs
tail -f logs/app.log
```

### Common Issues

1. **GitHub API Errors**
   - Verify token has correct permissions
   - Check organization access
   - Ensure token is not expired

2. **Database Connection Issues**
   - Verify database credentials in .env
   - Check MySQL service is running
   - Ensure database exists

3. **Port Conflicts**
   - Use different port: `./deploy.sh -p 9090`
   - Check what's using the port: `lsof -i :8081`

---

## License

MIT License - see [LICENSE](LICENSE) file for details.

---

## Support

- **Issues**: [GitHub Issues](https://github.com/Evolvus/github-smart/issues)
- **Documentation**: This README
- **API Reference**: See API Endpoints section above 
