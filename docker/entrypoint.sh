#!/usr/bin/env sh
set -e

# Generate .env files from environment variables
ENV_FILE_ROOT="/var/www/html/.env"
ENV_FILE_CONFIG="/var/www/html/config/.env"

# Create root .env file
if [ ! -f "$ENV_FILE_ROOT" ]; then
  echo "Creating $ENV_FILE_ROOT from environment variables..."
  cat > "$ENV_FILE_ROOT" <<EOF
APP_ENV=${APP_ENV:-production}

# Database
DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-project_management}
DB_USER=${DB_USER:-github_smart_user}
DB_PASSWORD=${DB_PASSWORD:-github_smart_password}

# Optional OrangeHRM (if used by legacy code)
DB_ORANGEHRM=${DB_ORANGEHRM:-orange}

# GitHub API
GITHUB_TOKEN=${GITHUB_TOKEN:-}
GITHUB_ORG=${GITHUB_ORG:-Syneca}

# Debugger flag used by legacy code
DEBUGGER=${DEBUGGER:-no}
EOF
fi

# Create config .env file
if [ ! -f "$ENV_FILE_CONFIG" ]; then
  echo "Creating $ENV_FILE_CONFIG from environment variables..."
  mkdir -p /var/www/html/config
  cat > "$ENV_FILE_CONFIG" <<EOF
APP_ENV=${APP_ENV:-production}

# Database
DB_HOST=${DB_HOST:-mysql}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-project_management}
DB_USER=${DB_USER:-github_smart_user}
DB_PASSWORD=${DB_PASSWORD:-github_smart_password}

# Optional OrangeHRM (if used by legacy code)
DB_ORANGEHRM=${DB_ORANGEHRM:-orange}

# GitHub API
GITHUB_TOKEN=${GITHUB_TOKEN:-}
GITHUB_ORG=${GITHUB_ORG:-Syneca}

# Debugger flag used by legacy code
DEBUGGER=${DEBUGGER:-no}
EOF
fi

exec "$@"


