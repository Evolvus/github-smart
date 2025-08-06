# GitHub Smart - Issue Management System

A production-ready PHP-based web application for managing and tracking GitHub issues with advanced filtering, analytics, and project management capabilities. Features a modern CI/CD pipeline with automated Docker deployments and one-touch deployment scripts.

## 📊 Project Status

- **🚀 Status**: Production Ready
- **🐳 Docker**: Fully containerized with multi-platform support
- **🔄 CI/CD**: Automated GitHub Actions pipeline
- **📦 Package**: Available on GitHub Container Registry
- **🛠️ Deployment**: One-touch deployment script available
- **📈 Version**: Latest stable release

## 🚀 Quick Start

**Deploy in 30 seconds:**
```bash
curl -fsSL https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh | bash -s -- -o YOUR_ORG -t YOUR_GITHUB_TOKEN
```

**Or download and run:**
```bash
curl -O https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh
chmod +x deploy.sh
./deploy.sh -o YOUR_ORG -t YOUR_GITHUB_TOKEN
```

## 🚀 Features

- **🔗 GitHub Integration**: Fetch and sync issues from GitHub organizations
- **📊 Dashboard Analytics**: Real-time statistics and charts
- **🔍 Advanced Filtering**: Filter by assignee, tags, projects, and more
- **📋 Project Management**: Organize issues by projects and buckets
- **👥 User Management**: Role-based access control
- **⚡ Real-time Updates**: Live data refresh and notifications
- **📋 DataTables Integration**: Advanced table features with sorting, filtering, and export
- **🛡️ Security Hardened**: Input validation, SQL injection protection, and comprehensive logging
- **🐳 Production Ready**: Docker-based deployment with health checks and monitoring

## 📋 Requirements

### Production Deployment
- **🐳 Docker**: 20.10 or higher
- **📦 Docker Compose**: 2.0 or higher (optional)
- **💾 Memory**: Minimum 2GB RAM
- **💿 Storage**: At least 5GB free space
- **🔑 GitHub Personal Access Token**: With `repo` permissions

### Development Setup
- **🐘 PHP**: 8.0 or higher
- **🗄️ MySQL**: 5.7 or higher
- **📦 Composer**: 2.0 or higher

## 🔄 Recent Updates

### Latest Improvements (v2.0)
- ✅ **One-Touch Deployment**: Automated deployment script with comprehensive error handling
- ✅ **CI/CD Pipeline**: GitHub Actions workflow with multi-platform Docker builds
- ✅ **Public Package**: Docker images available on GitHub Container Registry
- ✅ **Token Validation**: Automatic GitHub token validation and permission checking
- ✅ **Health Checks**: Real-time container health monitoring
- ✅ **Troubleshooting**: Comprehensive error handling and debugging information
- ✅ **Documentation**: Updated README with detailed deployment instructions

## 🛠️ Installation

### 🐳 Production Deployment (Recommended)

#### Option 1: One-Touch Deployment Script (Recommended)

The easiest way to deploy GitHub Smart is using the automated deployment script that handles everything for you.

**Prerequisites:**
- Docker installed and running
- GitHub Personal Access Token with `repo` permissions
- Internet connection

**Quick Start:**
```bash
# Download and run the deployment script
curl -fsSL https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh | bash -s -- -o YOUR_ORG -t YOUR_GITHUB_TOKEN
```

**Manual Deployment:**
```bash
# Download the script
curl -O https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh
chmod +x deploy.sh

# Run deployment
./deploy.sh -o YOUR_ORG -t YOUR_GITHUB_TOKEN
```

**Deployment Options:**
```bash
# Command line arguments
./deploy.sh -o Syneca -t ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx

# Environment variables
export GITHUB_ORG=Syneca
export GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
./deploy.sh

# Interactive mode (prompts for credentials)
./deploy.sh

# Custom configuration
./deploy.sh -o Syneca -t YOUR_TOKEN -p 9090 -n my-github-smart -i latest
```

**What the script does:**
- ✅ Validates your GitHub token and permissions
- ✅ Pulls the latest Docker image from GitHub Packages
- ✅ Creates all necessary configuration files automatically
- ✅ Starts the container with proper health checks
- ✅ Provides deployment status and access information

#### Option 2: Docker Compose (Advanced)

This method sets up a complete production environment with both the application and MySQL database.

1. **Clone and setup**
   ```bash
   git clone https://github.com/Evolvus/github-smart.git
   cd github-smart
   ```

2. **Configure environment**
   ```bash
   # Copy and edit the environment file
   cp docker.env.example docker.env
   nano docker.env
   ```

3. **Update configuration in docker.env:**
   ```env
   # Replace these values with your actual credentials
   GITHUB_TOKEN=your_github_token_here
   GITHUB_ORG=your_organization_name
   
   # Generate secure passwords (optional - defaults provided)
   MYSQL_ROOT_PASSWORD=your_secure_root_password_here
   MYSQL_PASSWORD=your_secure_password_here
   ```

4. **Deploy with Docker Compose**
   ```bash
   docker-compose up -d
   ```

5. **Access the application**
   - Application: http://localhost:8081
   - Database: localhost:3306

### Deployment Script Features

The `deploy.sh` script provides a comprehensive one-touch deployment experience:

- **🔐 Token Validation**: Automatically validates GitHub token and permissions
- **🐳 Docker Image Management**: Pulls latest images from GitHub Packages registry
- **⚙️ Automatic Configuration**: Creates `docker.env` file with all necessary settings
- **🔄 Container Management**: Stops and removes existing containers before deployment
- **💚 Health Checks**: Verifies container startup and provides real-time status
- **🎛️ Flexible Configuration**: Supports command-line arguments, environment variables, and interactive prompts
- **🛡️ Error Handling**: Comprehensive error checking with detailed troubleshooting
- **💾 Data Persistence**: Automatically creates and mounts data directory
- **📊 Status Reporting**: Provides clear feedback on deployment progress and results

## 🔄 CI/CD Pipeline

### GitHub Actions Workflow

The repository includes a modern GitHub Actions workflow that automatically builds and deploys Docker images to GitHub Packages:

- **🚀 Trigger**: Runs on pushes to `main`/`master` branches, pull requests, and releases
- **📦 Registry**: GitHub Container Registry (ghcr.io)
- **🐳 Image**: `ghcr.io/evolvus/github-smart:{tag}` (public package)
- **🖥️ Platforms**: Linux AMD64 and ARM64
- **⚡ Caching**: Uses GitHub Actions cache for faster builds
- **🏷️ Tags**: Automatic tagging based on branch, commit, and releases

**The workflow automatically:**
1. ✅ Sets up Docker Buildx for multi-platform builds
2. ✅ Logs in to GitHub Container Registry
3. ✅ Extracts metadata and creates appropriate tags
4. ✅ Builds and pushes the Docker image
5. ✅ Outputs image information for deployment

### Manual Build and Deploy

If you need to build and deploy manually:

```bash
# Build the Docker image
docker build -t ghcr.io/evolvus/github-smart:latest .

# Login to GitHub Container Registry
echo "your-token" | docker login ghcr.io -u evolvus --password-stdin

# Push the image
docker push ghcr.io/evolvus/github-smart:latest
```

#### Build from Source

1. **Clone and setup**
   ```bash
   git clone https://github.com/Evolvus/github-smart.git
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

## 🔧 Troubleshooting

### Common Issues

**Token Permissions:**
- Ensure your GitHub token has `repo` permissions
- Token format should be: `ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx`

**Docker Issues:**
- Make sure Docker is running: `docker info`
- Test connectivity: `docker pull hello-world`

**Deployment Issues:**
- Check container logs: `docker logs github-smart`
- Verify container status: `docker ps`

### Getting Help

- 📖 **Troubleshooting Guide**: See [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for detailed solutions
- 🐛 **Issues**: Report bugs on [GitHub Issues](https://github.com/Evolvus/github-smart/issues)
- 💬 **Discussions**: Join the conversation on [GitHub Discussions](https://github.com/Evolvus/github-smart/discussions)

## 🖥️ Development Setup

### Prerequisites
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher
- **Composer**: 2.0 or higher
- **Docker**: 20.10 or higher (for containerized development)

### Local Development

#### 1. Clone the Repository
```bash
git clone https://github.com/Evolvus/github-smart.git
cd github-smart
```

#### 2. Install Dependencies
```bash
composer install
```

#### 3. Environment Setup
```bash
# Copy environment file
cp docker.env.example docker.env
```

Edit `docker.env` with your configuration:
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

### Deployment Methods Comparison

| Feature | Docker Compose | Single Container (deploy.sh) |
|---------|----------------|------------------------------|
| **Database** | MySQL included | External database required |
| **Port** | 8081 | 8080 (configurable) |
| **Environment** | docker.env file | Direct environment variables |
| **Complexity** | Multi-container | Single container |
| **Production Ready** | ✅ Yes | ⚠️ Requires external DB |
| **Development** | ✅ Yes | ✅ Yes |

### Environment Files

The application uses different environment files for different setups:

#### For Docker Compose (Recommended)
```bash
# Copy the example file
cp docker.env.example docker.env

# Edit with your settings
nano docker.env
```

#### For Single Container (deploy.sh)
```bash
# Environment variables are passed directly to the container
# No separate environment file needed
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

### Docker Compose (Recommended for Production)
```bash
# Start all services
docker-compose up -d

# Stop all services
docker-compose down

# Restart all services
docker-compose restart

# Rebuild and start
docker-compose up --build -d

# View logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f mysql

# Check status
docker-compose ps
```

### Single Container (deploy.sh)
```bash
# Start container
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN

# Stop container
docker stop github-smart

# Remove container
docker rm github-smart

# View logs
docker logs github-smart

# Check status
docker ps | grep github-smart
```

### Access Containers (Docker Compose)
```bash
# PHP/Apache container
docker-compose exec app bash

# MySQL container
docker-compose exec mysql mysql -u root -p
```

### Database Operations (Docker Compose)
```bash
# Access MySQL
docker-compose exec mysql mysql -u root -p project_management

# Import data
docker-compose exec mysql mysql -u root -p project_management < create_tables.sql
```

### Access Single Container (deploy.sh)
```bash
# Access the container
docker exec -it github-smart bash

# Note: Single container deployment doesn't include MySQL
# You'll need to connect to an external database or use SQLite
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

We welcome contributions! Here's how you can help:

### 🚀 Quick Contribution
1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/amazing-feature`
3. **Make** your changes
4. **Test** your changes thoroughly
5. **Commit** with clear messages: `git commit -m "Add amazing feature"`
6. **Push** to your branch: `git push origin feature/amazing-feature`
7. **Submit** a pull request

### 📋 Contribution Guidelines
- ✅ Follow the existing code style
- ✅ Add tests for new features
- ✅ Update documentation as needed
- ✅ Ensure all tests pass
- ✅ Provide clear commit messages

### 🐛 Reporting Issues
- Use the GitHub issue template
- Include detailed reproduction steps
- Provide environment information
- Include relevant logs

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

### Getting Help
- 🐛 **Issues**: [GitHub Issues](https://github.com/Evolvus/github-smart/issues)
- 💬 **Discussions**: [GitHub Discussions](https://github.com/Evolvus/github-smart/discussions)
- 📖 **Documentation**: This README and [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
- 🔧 **Deployment Help**: Check the troubleshooting guide for common issues

### Quick Support Checklist
- ✅ Check container logs: `docker logs github-smart`
- ✅ Verify GitHub token permissions
- ✅ Ensure Docker is running: `docker info`
- ✅ Check application status: `docker ps`
- ✅ Review the troubleshooting guide

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
