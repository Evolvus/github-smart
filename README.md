# GitHub Smart

Production-ready PHP app to manage and visualize GitHub issues and projects.

## Requirements

- Docker 20.10+ and Docker Compose
- GitHub account (for GitHub Container Registry)

## Quickstart (local)

```bash
docker compose up -d
# App: http://localhost:8081
# MySQL: localhost:3308
```

Notes:
- Database tables are auto-created in local compose via `create_tables.sql`.
- Application logs are written to `logs/` on your host.

## Configuration

The container creates `config/.env` at runtime from environment variables:
- APP_ENV (default: production)
- DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD
- GITHUB_TOKEN (optional)

## CI/CD: Build and publish Docker image (GHCR)

On push to `main`, GitHub Actions builds and publishes the image to GitHub Container Registry (GHCR):
- Image: `ghcr.io/<owner>/github-smart:latest`
- Workflow: `.github/workflows/docker-publish.yml`

No extra setup required for public repos. For private repos, ensure Actions has “Write packages” permission in repo settings.

## One-touch deploy

This runs the app using the latest image from GHCR and starts a MySQL container if one is not running.

1) GHCR authentication is not required (package is public). Skip login.

2) Set optional app/database variables (or use defaults):
```bash
export APP_PORT=8081
export DB_NAME=project_management
export DB_USER=github_smart_user
export DB_PASSWORD=github_smart_password
export GITHUB_TOKEN=<your_github_token_optional>
```

2) Deploy:
```bash
./deploy.sh
```

App will be available at `http://localhost:${APP_PORT:-8081}`.

### First-time database initialization (deploy.sh path)

When using `deploy.sh`, the database container does not auto-run `create_tables.sql`.

Run once after containers start:
```bash
docker exec -i github-smart-mysql \
  sh -c 'mysql -u root -p"'${MYSQL_ROOT_PASSWORD:-github_smart_root_password}'"' < create_tables.sql
```

## Project structure

```
github-smart/
├── public/                  # Web root
├── src/                     # PHP source
├── config/                  # App/env config (runtime .env generated here)
├── scripts/                 # Utility scripts
├── docker/                  # Docker configs (nginx, supervisor, entrypoint)
├── docker-compose.yml       # Local compose (app + mysql)
├── Dockerfile               # Production image (php-fpm + nginx)
├── deploy.sh                # One-touch GHCR deploy
└── create_tables.sql        # Database schema
```

## Troubleshooting

- Cannot pull from GHCR: ensure you are logged in and the token has `read:packages`.
- App cannot connect to DB: verify `DB_HOST`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`.
- Port in use: change `APP_PORT` before running `deploy.sh`.

## License

MIT

# 🚀 GitHub Smart - Modern Issue Management System

> **Production-ready PHP application for managing GitHub issues with advanced analytics, filtering, and project management capabilities.**

[![Docker](https://img.shields.io/badge/Docker-Ready-blue?logo=docker)](https://hub.docker.com/)
[![GitHub Actions](https://img.shields.io/badge/CI/CD-GitHub%20Actions-green?logo=github)](https://github.com/Evolvus/github-smart/actions)
[![License](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.0+-purple?logo=php)](https://php.net/)

## 📋 Table of Contents

- [✨ Features](#-features)
- [🚀 Quick Start](#-quick-start)
- [📦 Installation](#-installation)
- [🔧 Configuration](#-configuration)
- [📊 Usage](#-usage)
- [🛠️ Development](#️-development)
- [🔧 Troubleshooting](#-troubleshooting)
- [🤝 Contributing](#-contributing)
- [📝 License](#-license)

## ✨ Features

### 🎯 Core Features
- **🔗 GitHub Integration**: Real-time sync with GitHub issues and projects
- **📊 Advanced Analytics**: Comprehensive dashboards with charts and metrics
- **🔍 Smart Filtering**: Filter by assignee, tags, projects, status, and more
- **📋 Project Management**: Organize issues by projects and custom buckets
- **👥 Team Management**: Role-based access and assignee tracking
- **⚡ Real-time Updates**: Live data refresh and notifications

### 🛡️ Security & Reliability
- **🔐 Token Validation**: Automatic GitHub token validation and permission checking
- **🛡️ Security Hardened**: Input validation, SQL injection protection, XSS prevention
- **📈 Health Monitoring**: Built-in health checks and comprehensive logging
- **🔄 Error Recovery**: Robust error handling and automatic retry mechanisms

### 🐳 Production Ready
- **📦 Docker Containerized**: Complete Docker setup with multi-platform support
- **🔄 CI/CD Pipeline**: Automated GitHub Actions with multi-platform builds
- **📦 Package Registry**: Available on GitHub Container Registry (ghcr.io)
- **🚀 One-Touch Deployment**: Automated deployment script with comprehensive setup

## 🚀 Quick Start

### ⚡ Deploy in 30 Seconds

**One-command deployment:**
```bash
rm -rf * && curl -fsSL https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh | bash -s -- -o YOUR_ORG -t YOUR_GITHUB_TOKEN
```

**Or download first, then run:**
```bash
# Download the deployment script
curl -O https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh

# Make it executable
chmod +x deploy.sh

# Run deployment
./deploy.sh -o YOUR_ORG -t YOUR_GITHUB_TOKEN
```

### 📋 Prerequisites

- **🐳 Docker**: 20.10+ (with Docker Compose)
- **💾 Memory**: 2GB+ RAM
- **💿 Storage**: 5GB+ free space
- **🔑 GitHub Token**: Personal Access Token with `repo` permissions

### 🎯 Example Deployment

```bash
# Deploy for your organization
curl -fsSL https://raw.githubusercontent.com/Evolvus/github-smart/main/deploy.sh | bash -s -- -o YOUR_ORG -t YOUR_GITHUB_TOKEN

# Deploy with custom port
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN -p 9090

# Deploy with environment variables
export GITHUB_ORG=YOUR_ORG
export GITHUB_TOKEN=YOUR_TOKEN
./deploy.sh
```

## 📦 Installation

### 🐳 Production Deployment (Recommended)

#### Option 1: One-Touch Deployment Script

The easiest way to deploy GitHub Smart with automatic setup and configuration.

**Features:**
- ✅ **Automatic Setup**: Downloads, configures, and starts everything
- ✅ **Token Validation**: Validates GitHub token and permissions
- ✅ **Health Checks**: Monitors container health and provides status
- ✅ **Error Handling**: Comprehensive error checking and troubleshooting
- ✅ **Flexible Configuration**: Command-line, environment variables, or interactive mode

**Usage:**
```bash
# Basic deployment
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN

# With custom options
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN -p 9090 -n my-github-smart

# Interactive mode (prompts for credentials)
./deploy.sh

# With environment variables
GITHUB_ORG=YOUR_ORG GITHUB_TOKEN=YOUR_TOKEN ./deploy.sh
```

**What the script does:**
1. 🔍 Validates your GitHub token and permissions
2. 🐳 Pulls the latest Docker image from GitHub Packages
3. ⚙️ Creates all necessary configuration files automatically
4. 🗄️ Sets up MySQL database with proper initialization
5. 🚀 Starts containers with health monitoring
6. 📊 Provides deployment status and access information

#### Option 2: Docker Compose (Advanced)

For advanced users who want full control over the deployment.

```bash
# Clone the repository
git clone https://github.com/Evolvus/github-smart.git
cd github-smart

# Configure environment
cp docker.env.example docker.env
nano docker.env

# Deploy with Docker Compose
docker-compose up -d
```

### 🛠️ Development Setup

#### Prerequisites
- **🐘 PHP**: 8.0+
- **🗄️ MySQL**: 5.7+
- **📦 Composer**: 2.0+

#### Local Development

```bash
# Clone and setup
git clone https://github.com/Evolvus/github-smart.git
cd github-smart

# Install dependencies
composer install

# Configure environment
cp .env.example .env
nano .env

# Setup database
mysql -u root -p < create_tables.sql

# Start development server
php -S localhost:8000 -t public/
```

## 🔧 Configuration

### 🔑 GitHub Token Setup

1. **Generate Token:**
   - Go to [GitHub Settings → Developer settings → Personal access tokens](https://github.com/settings/tokens)
   - Click "Generate new token (classic)"
   - Select scopes: `repo`, `read:org`, `read:user`, `read:project`

2. **Token Format:**
   ```
   ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
   ```

3. **Required Permissions:**
   - `repo` - Access to repository data
   - `read:org` - Access to organization data
   - `read:user` - Access to user information
   - `read:project` - Access to project data (GraphQL)

### 🐳 Docker Configuration

#### Environment Variables

**For deploy.sh (automatic):**
```bash
# The script creates docker.env automatically
# No manual configuration needed
```

**For Docker Compose:**
```env
# GitHub Configuration
GITHUB_TOKEN=your_github_token_here
GITHUB_ORG=your_organization_name

# Database Configuration
MYSQL_ROOT_PASSWORD=your_secure_root_password
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=your_secure_password

# Application Configuration
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=INFO
```

### 🔒 Security Configuration

```bash
# Generate secure passwords
openssl rand -base64 24

# Generate application key
openssl rand -hex 16
```

## 📊 Usage

### 🌐 Access the Application

After deployment, access the application at:
- **Main Dashboard**: http://localhost:8080
- **Database**: localhost:3307 (MySQL)

### 📋 Main Features

#### Dashboard Analytics
- **📊 Total Issues**: Real-time count of all issues
- **👥 Unassigned Issues**: Issues without assignees
- **📈 Top Assignees**: Chart of most active team members
- **🕒 Recent Activity**: Latest issues and updates
- **📅 Time Trends**: Issues over time visualization

#### Issue Management
- **🔍 Advanced Filtering**: Filter by assignee, tags, projects, status
- **📋 Project Organization**: Group issues by projects
- **🏷️ Tag Management**: Create and manage custom tags
- **👥 Assignee Tracking**: Track who's working on what
- **📌 Pin Important Issues**: Pin critical issues for visibility

#### Data Sync
- **🔄 Manual Sync**: Click "Retrieve GitHub Issues" to sync data
- **⚡ Real-time Updates**: Live data refresh
- **📊 Project Data**: Syncs both issues and project information

### 🎛️ API Endpoints

```bash
# Dashboard data
GET /api/getGHDash.php?action=total_count
GET /api/getGHDash.php?action=latest_issues
GET /api/getGHDash.php?action=by_project&projectId=ID
GET /api/getGHDash.php?action=by_assignee&assignee=NAME

# GitHub sync
POST /api/getGHIssues.php

# Project management
GET /api/getProjects.php
POST /api/add_bucket.php
DELETE /api/delete_bucket.php
```

## 🛠️ Development

### 🏗️ Project Structure

```
github-smart/
├── public/                 # Web root
│   ├── index.php          # Main entry point
│   ├── api/               # API endpoints
│   └── css/               # Stylesheets
├── src/                   # Application source
│   ├── Config/            # Configuration
│   ├── Database/          # Database management
│   ├── Services/          # Business logic
│   └── Security/          # Security middleware
├── config/                # Configuration files
├── scripts/               # Utility scripts
├── docker-compose.yml     # Docker configuration
├── deploy.sh             # Deployment script
└── README.md             # This file
```

### 🧪 Testing

```bash
# Run tests
composer test

# Static analysis
composer analyze

# Check container health
docker-compose ps
```

### 🔍 Debugging

```bash
# View application logs
docker-compose logs -f app

# View database logs
docker-compose logs -f mysql

# Access container shell
docker-compose exec app bash

# Check database
docker-compose exec mysql mysql -u root -p
```

## 🔧 Troubleshooting

### 🚨 Common Issues

#### 1. Docker Registry Access Issues

**Error**: `denied: denied` from ghcr.io

**Solutions**:
```bash
# Test Docker connectivity
docker pull hello-world

# Test GitHub Container Registry
docker pull ghcr.io/evolvus/github-smart:latest

# Check if image exists
curl -s https://ghcr.io/v2/evolvus/github-smart/tags/list
```

#### 2. GitHub Token Issues

**Error**: `401 Unauthorized` or token validation failures

**Solutions**:
```bash
# Test token manually
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.github.com/user

# Verify token permissions
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.github.com/user/repos
```

#### 3. Database Connection Issues

**Error**: MySQL connection failures

**Solutions**:
```bash
# Check container status
docker-compose ps

# View MySQL logs
docker-compose logs mysql

# Test database connection
docker-compose exec mysql mysql -u root -p -e "SELECT 1;"
```

#### 4. Port Conflicts

**Error**: `Bind for 0.0.0.0:8080 failed: port is already allocated`

**Solutions**:
```bash
# Use different port
./deploy.sh -o YOUR_ORG -t YOUR_TOKEN -p 9090

# Check what's using the port
lsof -i :8080

# Stop conflicting containers
docker stop $(docker ps -q)
```

### 🔍 Debugging Commands

```bash
# Check container status
docker-compose ps

# View all logs
docker-compose logs -f

# View specific service logs
docker-compose logs -f app
docker-compose logs -f mysql

# Access container shell
docker-compose exec app bash
docker-compose exec mysql bash

# Check database tables
docker-compose exec mysql mysql -u root -p project_management -e "SHOW TABLES;"

# Test application
curl -s http://localhost:8080 | head -10

# Test API
curl -X POST http://localhost:8080/api/getGHIssues.php
```

### 🛠️ Manual Database Setup

If automatic database initialization fails:

```bash
# Run manual database setup
./scripts/init_database.sh

# Or manually create tables
docker-compose exec mysql mysql -u root -p < create_tables.sql
```

### 📊 Health Checks

```bash
# Check application health
curl -f http://localhost:8080/ || echo "Application not responding"

# Check database health
docker-compose exec mysql mysqladmin -u root -p ping

# Check GitHub API
curl -H "Authorization: Bearer YOUR_TOKEN" https://api.github.com/user
```

## 🤝 Contributing

We welcome contributions! Here's how to get started:

### 🚀 Quick Start

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/amazing-feature`
3. **Make** your changes
4. **Test** thoroughly
5. **Commit** with clear messages: `git commit -m "Add amazing feature"`
6. **Push** to your branch: `git push origin feature/amazing-feature`
7. **Submit** a pull request

### 📋 Guidelines

- ✅ Follow existing code style
- ✅ Add tests for new features
- ✅ Update documentation
- ✅ Ensure all tests pass
- ✅ Provide clear commit messages

### 🐛 Reporting Issues

- Use the GitHub issue template
- Include detailed reproduction steps
- Provide environment information
- Include relevant logs

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### 📖 Documentation
- **📋 This README**: Complete setup and usage guide
- **🔧 Troubleshooting**: See troubleshooting section above
- **📚 API Docs**: Inline documentation in code

### 🆘 Getting Help
- **🐛 Issues**: [GitHub Issues](https://github.com/Evolvus/github-smart/issues)
- **💬 Discussions**: [GitHub Discussions](https://github.com/Evolvus/github-smart/discussions)
- **📧 Email**: Contact the maintainers

---

<div align="center">

**Made with ❤️ by the GitHub Smart Team**

[![GitHub](https://img.shields.io/badge/GitHub-Repository-blue?logo=github)](https://github.com/Evolvus/github-smart)
[![Docker](https://img.shields.io/badge/Docker-Image-blue?logo=docker)](https://github.com/Evolvus/github-smart/packages)
[![Issues](https://img.shields.io/badge/Issues-Welcome-green?logo=github)](https://github.com/Evolvus/github-smart/issues)

</div> 
