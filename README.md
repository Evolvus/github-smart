# GitHub Smart - Issue Management System

A PHP-based web application for managing and tracking GitHub issues with advanced filtering, analytics, and project management capabilities.

## 🚀 Features

- **GitHub Integration**: Fetch and sync issues from GitHub organizations
- **Dashboard Analytics**: Real-time statistics and charts
- **Advanced Filtering**: Filter by assignee, tags, projects, and more
- **Project Management**: Organize issues by projects and buckets
- **User Management**: Role-based access control
- **Real-time Updates**: Live data refresh and notifications

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer
- GitHub Personal Access Token

## 🛠️ Installation

### 1. Clone the Repository
```bash
git clone <repository-url>
cd github-smart
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Environment Setup
Copy the environment file and configure your settings:
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

### 4. Database Setup
```bash
mysql -u root -p < create_tables.sql
```

### 5. Start Development Server
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
│   └── ...
├── src/                    # Application source code
│   ├── Config/            # Configuration classes
│   ├── Database/          # Database management
│   ├── Services/          # Business logic services
│   └── Models/            # Data models
├── css/                   # Stylesheets
├── vendor/                # Composer dependencies
├── .env                   # Environment configuration
├── composer.json          # Dependencies
└── README.md             # This file
```

## 🔒 Security Features

- **Input Validation**: All user inputs are sanitized and validated
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Protection**: HTML escaping for all output
- **Error Handling**: Comprehensive error logging without exposing sensitive data
- **Environment-based Configuration**: Separate configs for development/production

## 🚀 Usage

### Dashboard
Visit `http://localhost:8000` to access the main dashboard with:
- Total issue count
- Unassigned issues
- Top assignees chart
- Latest issues list
- Issues over time chart

### API Endpoints
- `GET /api/getGHDash.php?action=total_count` - Get total issues count
- `GET /api/getGHDash.php?action=latest_issues` - Get latest issues
- `POST /api/getGHIssues.php` - Sync issues from GitHub

### Manual GitHub Sync
```bash
curl -X POST http://localhost:8000/api/getGHIssues.php
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

## 🔄 Changelog

### v1.0.0
- Initial release
- GitHub integration
- Dashboard analytics
- Issue management features 