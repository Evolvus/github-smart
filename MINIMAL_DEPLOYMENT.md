# Minimal Production Deployment

You can deploy GitHub Smart to production without cloning the entire repository. Here's the minimal approach:

## Option 1: Download and Run Script (Recommended)

### Step 1: Download the deployment script
```bash
# Create a fresh directory
mkdir github-smart-production
cd github-smart-production

# Download the deployment script
curl -O https://raw.githubusercontent.com/evolvus/github-smart/main/scripts/deploy-production.sh
chmod +x deploy-production.sh
```

### Step 2: Run the deployment script
```bash
./deploy-production.sh
```

## Option 2: Manual Minimal Setup

If you prefer manual setup, here are the minimal files you need:

### Step 1: Create directory and files
```bash
mkdir github-smart-production
cd github-smart-production
```

### Step 2: Create docker-compose.yml
```bash
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
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      timeout: 20s
      retries: 10

volumes:
  mysql_data:
EOF
```

### Step 3: Create .env file
```bash
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
DB_PASSWORD=your_secure_password_here

# MySQL Configuration
MYSQL_ROOT_PASSWORD=your_secure_root_password_here
MYSQL_DATABASE=project_management
MYSQL_USER=github_smart_user
MYSQL_PASSWORD=your_secure_password_here

# GitHub Configuration
GITHUB_TOKEN=${GITHUB_TOKEN:-your_github_token_here}
GITHUB_ORG=${GITHUB_ORG:-your_organization_name}

# Security Configuration
APP_KEY=your_random_32_character_string_here
EOF
```

### Step 4: Deploy
```bash
# Pull the image
docker pull ghcr.io/evolvus/github-smart:latest

# Start services
docker-compose up -d

# Check status
docker-compose ps
```

## What You Get

With either approach, you'll have:
- ✅ Production-ready Docker setup
- ✅ MySQL database with persistent storage
- ✅ Health checks and monitoring
- ✅ Easy updates and maintenance
- ✅ No need to clone the entire repository

## Next Steps

### Option 1: Use Environment Variables (Recommended)
```bash
# Set your GitHub credentials as environment variables
export GITHUB_TOKEN=your_github_personal_access_token
export GITHUB_ORG=your_organization_name

# Restart the application to pick up the new environment variables
docker-compose restart
```

### Option 2: Edit the .env file manually
1. **Update the .env file** with your actual values:
   - `GITHUB_TOKEN`: Your GitHub Personal Access Token
   - `GITHUB_ORG`: Your organization name
   - `DB_PASSWORD`: A secure password
   - `MYSQL_ROOT_PASSWORD`: A secure root password
   - `APP_KEY`: A random 32-character string

2. **Restart the application**:
   ```bash
   docker-compose restart
   ```

3. **Access your application** at `http://your-server-ip`

## Useful Commands

```bash
# Check status
docker-compose ps

# View logs
docker-compose logs -f

# Update to latest version
docker pull ghcr.io/evolvus/github-smart:latest
docker-compose down && docker-compose up -d

# Stop services
docker-compose down
```

That's it! You can deploy GitHub Smart to production with just these minimal files and no need to clone the entire repository. 