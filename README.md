# GitHub Smart - Issue Management System

A production-ready PHP-based web application for managing and tracking GitHub issues with advanced filtering, analytics, and project management capabilities.

## 🚀 Features

- **GitHub Integration**: Fetch and sync issues from GitHub organizations
- **Dashboard Analytics**: Real-time statistics and charts
- **Advanced Filtering**: Filter by assignee, tags, projects, and more
- **Project Management**: Organize issues by projects and buckets
- **User Management**: Role-based access control
- **Real-time Updates**: Live data refresh and notifications
- **DataTables Integration**: Advanced table features with sorting, filtering, and export
- **Security Hardened**: Input validation, SQL injection protection, and comprehensive logging
- **Production Ready**: Docker-based deployment with health checks and monitoring

## 📋 Requirements

### Production Deployment
- **Docker**: 20.10 or higher
- **Docker Compose**: 2.0 or higher
- **Memory**: Minimum 2GB RAM
- **Storage**: At least 5GB free space
- **GitHub Personal Access Token**

### Development Setup
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Composer**: 2.0 or higher

## 🛠️ Installation

### 🐳 Production Deployment (Recommended)

#### Quick Start with Docker

1. **One-Click Installation**
   ```bash
   # Download and run the installer
   curl -fsSL https://raw.githubusercontent.com/evolvus/github-smart/main/install.sh | bash -s -- -o evolvus -t YOUR_GITHUB_TOKEN
   ```

2. **Manual Installation**
   ```bash
   # Download the deployment script
   curl -O https://raw.githubusercontent.com/evolvus/github-smart/main/deploy.sh
   chmod +x deploy.sh
   
   # Run with your GitHub credentials
   ./deploy.sh -o evolvus -t YOUR_GITHUB_TOKEN
   ```

3. **Using Environment Variables**
   ```bash
   export GITHUB_ORG="evolvus"
   export GITHUB_TOKEN="your-github-token"
   curl -fsSL https://raw.githubusercontent.com/evolvus/github-smart/main/install.sh | bash
   ```

#### Build from Source

1. **Clone and setup**
   ```bash
   git clone <repository-url>
   cd github-smart
   ```

2. **Build Docker image**
   ```bash
   docker build -t ghcr.io/evolvus/github-smart:latest .
   ```

3. **Push to GitHub Packages**
   ```bash
   echo $GITHUB_TOKEN | docker login ghcr.io -u evolvus --password-stdin
   docker push ghcr.io/evolvus/github-smart:latest
   ```

### 🖥️ Development Setup

#### 1. Clone the Repository
```bash
git clone <repository-url>
cd github-smart
```

#### 2. Install Dependencies
```bash
composer install
```

#### 3. Environment Setup
```bash
# Setup environment files
./setup-env.sh
```

Edit `.env` with your configuration:
```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_NAME=project_management
DB_USER=root
DB_PASSWORD=your_password

# GitHub API Configuration
GITHUB_TOKEN=your_github_token
GITHUB_ORG=your_organization

# Application Settings
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=INFO
```

#### 4. Database Setup
```bash
mysql -u root -p < create_tables.sql
```

#### 5. Start Development Server
```bash
# Set document root to public directory
php -S localhost:8000 -t public/
```

## 🔧 Configuration

### Environment Files

The application uses different environment files for different setups:

#### For Production Deployment
```bash
# Copy the example file
cp docker.env.example docker.env

# Edit with your settings
nano docker.env
```

#### For Development Setup
```bash
# Copy the example file
cp .env.example .env

# Edit with your settings
nano .env
```

### GitHub Token Setup
1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Generate a new token with the following scopes:
   - `repo` (for private repositories)
   - `read:org` (for organization access)
   - `read:user` (for user information)
   - `read:project` (for project access - required for GraphQL)

### Production Security

1. **Generate secure passwords**
   ```bash
   # Generate random passwords
   openssl rand -base64 24
   ```

2. **Set APP_KEY**
   ```bash
   # Generate a random 32-character string
   openssl rand -base64 24
   ```

3. **Configure environment**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   LOG_LEVEL=INFO
   ```

## 📁 Project Structure

```
github-smart/
├── public/                    # Web root (Apache/Nginx document root)
│   ├── index.php             # Main entry point
│   ├── people.php            # People/assignee management
│   ├── projects.php          # Project management
│   ├── customer.php          # Customer management
│   ├── issues.php            # Issue listing
│   ├── tag.php              # Tag management
│   ├── bucket.php            # Bucket management
│   ├── pin.php              # Pin management
│   ├── head.php             # HTML head template
│   ├── bodyend.php          # HTML body end template
│   ├── bootstrap.php         # Application bootstrap
│   ├── .htaccess            # Apache configuration
│   └── css/                 # Stylesheets
│       └── bootstrap.min.css
├── src/                      # Application source code
│   ├── Config/              # Configuration classes
│   │   └── AppConfig.php
│   ├── Database/            # Database management
│   │   └── DatabaseManager.php
│   ├── Services/            # Business logic services
│   │   └── GitHubService.php
│   ├── Security/            # Security middleware
│   │   └── SecurityMiddleware.php
│   ├── Controllers/         # MVC controllers (future)
│   ├── Models/              # Data models (future)
│   └── Views/               # View templates (future)
├── api/                     # API endpoints
│   ├── getGHIssues.php      # GitHub issue retrieval
│   ├── getGHDash.php        # Dashboard data
│   ├── utilities_project.php # Project utilities
│   ├── getProjects.php      # Project listing
│   ├── get_buckets.php      # Bucket operations
│   ├── get_tags.php         # Tag operations
│   ├── pin_issue.php        # Pin operations
│   ├── addCustomer.php      # Customer operations
│   ├── removeCustomer.php   # Customer operations
│   ├── add_bucket.php       # Bucket operations
│   ├── delete_bucket.php    # Bucket operations
│   ├── update_bucket_name.php # Bucket operations
│   └── update_issue_bucket.php # Bucket operations
├── config/                  # Configuration files
│   ├── app.php             # Application configuration
│   └── database.php        # Database configuration
├── tests/                   # Unit tests
│   ├── Unit/               # Unit tests
│   ├── Integration/        # Integration tests
│   └── test_*.php          # Test files
├── docs/                    # Documentation
│   ├── PROJECT_STRUCTURE.md # Project structure
│   ├── PRODUCTION_DEPLOYMENT.md # Production deployment guide
│   ├── API.md              # API documentation
│   ├── DEPLOYMENT.md       # Deployment guide
│   └── SECURITY.md         # Security documentation
├── scripts/                 # Utility scripts
│   ├── deploy-production.sh # Production deployment script
│   ├── setup_database.php  # Database setup
│   ├── setup_cli.php       # CLI setup
│   └── monitor_logs.php    # Log monitoring
├── logs/                    # Log files
│   └── app.log             # Application logs
├── uploads/                 # File uploads
├── vendor/                  # Composer dependencies
├── docker-compose.yml       # Docker configuration
├── docker.env              # Docker environment (gitignored)
├── docker.env.example      # Docker environment template
├── Dockerfile              # Docker image definition
├── composer.json           # Dependencies
├── composer.lock           # Dependency lock
├── .env                    # Environment configuration
├── .env.example            # Environment template
├── .gitignore              # Git ignore rules
├── create_tables.sql       # Database schema
├── README.md               # Main documentation
└── LICENSE                 # License file
```

## 🔒 Security Features

- **Input Validation**: All user inputs are sanitized and validated
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Protection**: HTML escaping for all output
- **CSRF Protection**: Basic CSRF token validation
- **Security Headers**: Comprehensive security headers
- **Error Handling**: Comprehensive error logging without exposing sensitive data
- **Environment-based Configuration**: Separate configs for development/production
- **Rate Limiting**: Basic rate limiting implementation
- **Session Security**: Secure session configuration
- **Non-root Docker User**: Application runs as non-root user
- **Health Checks**: Automatic health monitoring

## 🚀 Usage

### Dashboard
Visit the application URL to access the main dashboard with:
- Total issue count
- Unassigned issues
- Top assignees chart
- Latest issues list
- Issues over time chart

### Pages
- **Dashboard** (`/`): Main analytics and overview
- **Projects** (`/projects.php`): Project-wise issue management
- **People** (`/people.php`): Assignee-wise issue management
- **Issues** (`/issues.php`): All issues with filtering
- **Tags** (`/tag.php`): Tag-based issue management
- **Customers** (`/customer.php`): Customer-wise issue management

### API Endpoints
- `GET /api/getGHDash.php?action=total_count` - Get total issues count
- `GET /api/getGHDash.php?action=latest_issues` - Get latest issues
- `GET /api/getGHDash.php?action=by_project&projectId=ID` - Get issues by project
- `GET /api/getGHDash.php?action=by_assignee&assignee=NAME` - Get issues by assignee
- `POST /api/getGHIssues.php` - Sync issues from GitHub

### Manual GitHub Sync
```bash
# Production deployment
docker-compose exec app php api/getGHIssues.php

# Development setup
curl -X POST http://localhost:8000/api/getGHIssues.php
```

## 🐳 Docker Commands

### Production Deployment
```bash
# Deploy application
./scripts/deploy-production.sh deploy

# Check status
./scripts/deploy-production.sh status

# View logs
./scripts/deploy-production.sh logs

# Stop application
./scripts/deploy-production.sh stop

# Restart application
./scripts/deploy-production.sh restart
```

### Container Management
```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# Restart containers
docker-compose restart

# Rebuild containers
docker-compose up --build -d
```

### Access Containers
```bash
# PHP/Apache container
docker-compose exec app bash

# MySQL container
docker-compose exec mysql mysql -u root -p
```

### Database Operations
```bash
# Access MySQL
docker-compose exec mysql mysql -u root -p project_management

# Import data
docker-compose exec mysql mysql -u root -p project_management < create_tables.sql
```

## 📊 Monitoring

### Health Checks
The application includes automatic health checks:
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
Application logs are stored in the container:
```bash
# View application logs
docker-compose exec app tail -f /var/www/html/logs/app.log
```

## 🔄 Deployment

### Production Checklist
1. Set `APP_ENV=production` in `docker.env`
2. Set `APP_DEBUG=false` in `docker.env`
3. Configure proper database credentials
4. Set up HTTPS (if applicable)
5. Configure proper file permissions
6. Set up backup strategy
7. Configure monitoring

### GitHub Package Registry
The application is automatically built and published to GitHub Container Registry on each release.

### Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

#### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 🧪 Testing

Run the test suite:
```bash
composer test
```

Run static analysis:
```bash
composer analyze
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue in the repository
- Check the logs in `app.log`
- Verify your GitHub token permissions
- Ensure database connectivity

### Troubleshooting

#### Docker Issues
- Ensure Docker Desktop is running
- Check container logs: `docker-compose logs -f`
- Verify ports 8081 and 3306 are available

#### GitHub API Issues
- Verify GitHub token has correct permissions
- Check GraphQL API access for projects
- Review API rate limits

#### Database Issues
- Verify database connection settings
- Check MySQL service is running
- Review database permissions

#### Production Deployment Issues
- Run environment check: `./scripts/deploy-production.sh check`
- Verify all environment variables are set
- Check system resources (memory, disk space)

## 🔄 Changelog

### v2.0.0
- **Production Docker Setup**: Complete rewrite for production deployment
- **GitHub Package Registry**: Automated builds and publishing
- **Security Enhancements**: Non-root user, security headers, health checks
- **Deployment Scripts**: Automated deployment and management
- **Multi-stage Builds**: Optimized Docker images
- **Health Monitoring**: Built-in health checks and monitoring

### v1.1.0
- Added Docker support
- Enhanced security features
- Improved error handling
- Added comprehensive logging

### v1.0.0
- Initial release
- GitHub integration
- Dashboard analytics
- Issue management features 
