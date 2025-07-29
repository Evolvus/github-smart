# GitHub Smart - Issue Management System

A PHP-based web application for managing and tracking GitHub issues with advanced filtering, analytics, and project management capabilities.

## 🚀 Features

- **GitHub Integration**: Fetch and sync issues from GitHub organizations
- **Dashboard Analytics**: Real-time statistics and charts
- **Advanced Filtering**: Filter by assignee, tags, projects, and more
- **Project Management**: Organize issues by projects and buckets
- **User Management**: Role-based access control
- **Real-time Updates**: Live data refresh and notifications
- **DataTables Integration**: Advanced table features with sorting, filtering, and export
- **Security Hardened**: Input validation, SQL injection protection, and comprehensive logging

## 📋 Requirements

### Option 1: Traditional Setup
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Composer**: 2.0 or higher
- **GitHub Personal Access Token**

### Option 2: Docker Setup (Recommended)
- **Docker Desktop**: Latest version
- **Docker Compose**: Included with Docker Desktop
- **GitHub Personal Access Token**

## 🛠️ Installation

### 🐳 Option 1: Docker Setup (Recommended)

#### Quick Start
```bash
# Clone the repository
git clone <repository-url>
cd github-smart

# Copy environment file
cp docker.env .env

# Edit .env with your GitHub token
nano .env

# Start the application
./start-docker.sh
```

#### Manual Docker Setup
```bash
# Build and start containers
docker-compose up --build -d

# Access the application
# Web: http://localhost:8080
# MySQL: localhost:3306
```

#### Docker Environment Configuration
Edit `docker.env` or `.env`:
```env
# GitHub Configuration
GITHUB_TOKEN=your_github_token_here
GITHUB_ORG=Syneca

# Database Configuration
DB_HOST=mysql
DB_PORT=3306
DB_NAME=project_management
DB_USER=root
DB_PASSWORD=Evolvus*123

# Application Settings
APP_ENV=development
APP_DEBUG=true
LOG_LEVEL=INFO
```

### 🖥️ Option 2: Traditional Setup

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
cp .env.example .env
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
php -S localhost:8000
```

## 🔧 Configuration

### GitHub Token Setup
1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Generate a new token with the following scopes:
   - `repo` (for private repositories)
   - `read:org` (for organization access)
   - `read:user` (for user information)
   - `read:project` (for project access - required for GraphQL)

### Database Configuration
The application uses MySQL with the following main tables:
- `gh_issues`: Stores GitHub issues
- `gh_projects`: Stores project information
- `gh_issue_tags`: Stores issue labels/tags
- `gh_audit`: Stores audit logs

## 📁 Project Structure

```
github-smart/
├── api/                    # API endpoints
│   ├── getGHIssues.php    # GitHub issue retrieval
│   ├── getGHDash.php      # Dashboard data
│   ├── utilities_project.php # Project utilities
│   └── ...
├── src/                    # Application source code
│   ├── Config/            # Configuration classes
│   ├── Database/          # Database management
│   ├── Services/          # Business logic services
│   ├── Security/          # Security middleware
│   └── Models/            # Data models
├── css/                   # Stylesheets
├── vendor/                # Composer dependencies
├── docker-compose.yml     # Docker configuration
├── docker.env             # Docker environment
├── start-docker.sh        # Docker startup script
├── .env                   # Environment configuration
├── composer.json          # Dependencies
└── README.md             # This file
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
# Traditional setup
curl -X POST http://localhost:8000/api/getGHIssues.php

# Docker setup
docker-compose exec app php api/getGHIssues.php
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

## 📊 Monitoring

### Logs
Application logs are stored in `app.log` with different levels:
- `INFO`: General application events
- `ERROR`: Error conditions
- `WARNING`: Warning conditions
- `DEBUG`: Debug information

### Docker Logs
```bash
# View all logs
docker-compose logs -f

# View specific container logs
docker-compose logs -f app
docker-compose logs -f mysql
```

### Database Monitoring
Monitor database performance with:
```sql
-- Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'project_management';

-- Check recent activity
SELECT * FROM gh_audit ORDER BY end_time DESC LIMIT 10;
```

## 🔄 Deployment

### Production Checklist
1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Configure proper database credentials
4. Set up HTTPS
5. Configure web server (Apache/Nginx)
6. Set up proper file permissions
7. Configure backup strategy

### Docker Production
```bash
# Build production image
docker-compose -f docker-compose.prod.yml up --build -d
```

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

## 🐳 Docker Commands

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
docker-compose exec mysql mysql -u root -pEvolvus*123 project_management

# Import data
docker-compose exec mysql mysql -u root -pEvolvus*123 project_management < create_tables.sql
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
- Verify ports 8080 and 3306 are available

#### GitHub API Issues
- Verify GitHub token has correct permissions
- Check GraphQL API access for projects
- Review API rate limits

#### Database Issues
- Verify database connection settings
- Check MySQL service is running
- Review database permissions

## 🔄 Changelog

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