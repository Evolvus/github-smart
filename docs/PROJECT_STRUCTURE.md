# Project Structure

This document describes the organization of the GitHub Smart application.

## Directory Structure

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
│   ├── PROJECT_STRUCTURE.md # This file
│   ├── API.md              # API documentation
│   ├── DEPLOYMENT.md       # Deployment guide
│   └── SECURITY.md         # Security documentation
├── scripts/                 # Utility scripts
│   ├── setup_database.php  # Database setup
│   ├── setup_cli.php       # CLI setup
│   └── monitor_logs.php    # Log monitoring
├── logs/                    # Log files
│   └── app.log             # Application logs
├── uploads/                 # File uploads
├── vendor/                  # Composer dependencies
├── docker-compose.yml       # Docker configuration
├── docker.env              # Docker environment
├── start-docker.sh         # Docker startup script
├── composer.json           # Dependencies
├── composer.lock           # Dependency lock
├── .env                    # Environment configuration
├── .env.example            # Environment template
├── .gitignore              # Git ignore rules
├── create_tables.sql       # Database schema
├── README.md               # Main documentation
└── LICENSE                 # License file
```

## Key Principles

### 1. Separation of Concerns
- **Public files** in `/public/` - Only files that should be directly accessible via web
- **Application logic** in `/src/` - Business logic, services, and models
- **API endpoints** in `/api/` - REST API endpoints
- **Configuration** in `/config/` - Application configuration files

### 2. Security
- **Document root** is `/public/` - Prevents access to sensitive files
- **Configuration files** outside web root - Prevents exposure of sensitive data
- **Logs** in separate directory - Prevents log access via web

### 3. Maintainability
- **Namespaced classes** in `/src/` - Follows PSR-4 autoloading
- **Organized by feature** - Related files grouped together
- **Clear dependencies** - Explicit require statements

## File Naming Conventions

### PHP Files
- **Classes**: PascalCase (e.g., `AppConfig.php`)
- **Functions**: camelCase (e.g., `getProjectIssues()`)
- **Files**: snake_case (e.g., `get_gh_issues.php`)

### Directories
- **Namespaces**: PascalCase (e.g., `Config/`, `Database/`)
- **Features**: lowercase (e.g., `api/`, `docs/`)

## Configuration

### Environment Variables
- **Development**: `.env` file (from `.env.example`)
- **Docker**: `docker.env` file (from `docker.env.example`)
- **Production**: Environment variables

### Database
- **Schema**: `create_tables.sql`
- **Connection**: `config/database.php`
- **Configuration**: Environment variables

## Development Workflow

### Adding New Features
1. **Controllers**: Add to `/src/Controllers/`
2. **Models**: Add to `/src/Models/`
3. **Services**: Add to `/src/Services/`
4. **Views**: Add to `/src/Views/`
5. **API Endpoints**: Add to `/api/`

### Testing
1. **Unit Tests**: Add to `/tests/Unit/`
2. **Integration Tests**: Add to `/tests/Integration/`
3. **Run Tests**: `composer test`

### Documentation
1. **API Docs**: Add to `/docs/API.md`
2. **Deployment**: Add to `/docs/DEPLOYMENT.md`
3. **Security**: Add to `/docs/SECURITY.md`

## Deployment

### Traditional Setup
- **Document root**: `/public/`
- **Configuration**: `/config/`
- **Logs**: `/logs/`

### Docker Setup
- **Document root**: `/var/www/html/public/`
- **Configuration**: `/var/www/html/config/`
- **Logs**: `/var/www/html/logs/`

## Security Considerations

### File Access
- **Public files**: Only in `/public/`
- **Configuration**: Outside web root
- **Logs**: Outside web root
- **Uploads**: Restricted access

### Configuration
- **Environment variables**: For sensitive data
- **Configuration files**: For application settings
- **Database credentials**: In environment variables

### Logging
- **Application logs**: `/logs/app.log`
- **Error logs**: `/logs/error.log`
- **Access logs**: `/logs/access.log` 