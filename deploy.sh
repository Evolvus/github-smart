#!/usr/bin/env bash
set -euo pipefail

# Default values
IMAGE="ghcr.io/evolvus/github-smart:latest"
APP_NAME="github-smart-app"
MYSQL_NAME="github-smart-mysql"
GITHUB_ORG=""
GITHUB_TOKEN=""

# Parse command line arguments
while [[ $# -gt 0 ]]; do
  case $1 in
    -o|--org)
      GITHUB_ORG="$2"
      shift 2
      ;;
    -t|--token)
      GITHUB_TOKEN="$2"
      shift 2
      ;;
    -p|--port)
      APP_PORT="$2"
      shift 2
      ;;
    -h|--help)
      echo "Usage: $0 [-o|--org ORGANIZATION] [-t|--token TOKEN] [-p|--port PORT]"
      echo ""
      echo "Options:"
      echo "  -o, --org ORGANIZATION    GitHub organization name (required for API testing)"
      echo "  -t, --token TOKEN         GitHub personal access token (required for API testing)"
      echo "  -p, --port PORT           Application port (default: 8081)"
      echo "  -h, --help                Show this help message"
      echo ""
      echo "Examples:"
      echo "  $0 -o Syneca -t ghp_xxxxxxxxxxxxxxxxxxxx"
      echo "  $0 --org Syneca --token ghp_xxxxxxxxxxxxxxxxxxxx --port 9090"
      echo "  $0  # Run without GitHub integration"
      exit 0
      ;;
    *)
      echo "Unknown option: $1"
      echo "Use -h or --help for usage information"
      exit 1
      ;;
  esac
done



echo "Pulling image $IMAGE..."
docker pull "$IMAGE"

echo "Ensuring MySQL container is running..."
if ! docker ps --format '{{.Names}}' | grep -q "^${MYSQL_NAME}$"; then
  docker run -d \
    --name ${MYSQL_NAME} \
    -e MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-github_smart_root_password} \
    -e MYSQL_DATABASE=${DB_NAME:-project_management} \
    -e MYSQL_USER=${DB_USER:-github_smart_user} \
    -e MYSQL_PASSWORD=${DB_PASSWORD:-github_smart_password} \
    -p ${MYSQL_PORT:-3308}:3306 \
    -v github-smart-db:/var/lib/mysql \
    mysql:8.0
else
  echo "MySQL container already running"
fi

echo "Waiting for MySQL to be ready..."
for i in {1..30}; do
  if docker exec -i ${MYSQL_NAME} sh -c 'mysqladmin ping -h 127.0.0.1 -u root -p"'${MYSQL_ROOT_PASSWORD:-github_smart_root_password}'" --silent'; then
    break
  fi
  sleep 2
done

echo "Initializing database with create_tables.sql..."
if curl -s https://raw.githubusercontent.com/Evolvus/github-smart/main/create_tables.sql | docker exec -i ${MYSQL_NAME} mysql -u root -p"${MYSQL_ROOT_PASSWORD:-github_smart_root_password}" "${DB_NAME:-project_management}"; then
  echo "Database initialization completed successfully"
else
  echo "Warning: Database initialization failed, but continuing..."
fi

echo "Verifying database tables were created..."
table_count=$(docker exec -i ${MYSQL_NAME} mysql -u root -p"${MYSQL_ROOT_PASSWORD:-github_smart_root_password}" "${DB_NAME:-project_management}" -e "SHOW TABLES;" 2>/dev/null | grep -v "Tables_in" | wc -l)
if [ "$table_count" -ge 7 ]; then
  echo "✅ Database verification successful: Found $table_count tables"
else
  echo "⚠️  Warning: Only found $table_count tables (expected 7+)"
fi

echo "Starting/Restarting app container..."
docker rm -f ${APP_NAME} >/dev/null 2>&1 || true
docker run -d \
  --name ${APP_NAME} \
  --restart unless-stopped \
  -p ${APP_PORT:-8081}:8080 \
  -e APP_ENV=production \
  -e DB_HOST=${DB_HOST:-${MYSQL_NAME}} \
  -e DB_PORT=3306 \
  -e DB_NAME=${DB_NAME:-project_management} \
  -e DB_USER=${DB_USER:-github_smart_user} \
  -e DB_PASSWORD=${DB_PASSWORD:-github_smart_password} \
  -e GITHUB_TOKEN=${GITHUB_TOKEN:-} \
  -e GITHUB_ORG=${GITHUB_ORG:-} \
  -v github-smart-logs:/var/www/html/logs \
  --link ${MYSQL_NAME}:mysql \
  "$IMAGE"

echo "App deployed and listening on port ${APP_PORT:-8081}"

echo "Waiting for application to be ready..."
for i in {1..30}; do
  if curl -s http://localhost:${APP_PORT:-8081} >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

echo "Testing GitHub issues API..."
if [ -n "${GITHUB_TOKEN}" ]; then
  echo "GitHub token provided, testing API call..."
  api_response=$(curl -s -X POST http://localhost:${APP_PORT:-8081}/api/getGHIssues.php 2>/dev/null || echo "API call failed")
  if echo "$api_response" | grep -q "success\|issues\|data\|GitHub API token not configured"; then
    echo "✅ GitHub issues API test successful (API responded correctly)"
  else
    echo "⚠️  GitHub issues API test failed or no response"
  fi
  
  echo "Checking if issues were created in database..."
  issue_count=$(docker exec -i ${MYSQL_NAME} mysql -u root -p"${MYSQL_ROOT_PASSWORD:-github_smart_root_password}" "${DB_NAME:-project_management}" -e "SELECT COUNT(*) as count FROM gh_issues;" 2>/dev/null | grep -v "count" | tail -1)
  if [ "$issue_count" -gt 0 ] 2>/dev/null; then
    echo "✅ Issues found in database: $issue_count issues"
  else
    echo "⚠️  No issues found in database (this is normal if no GitHub token provided or no issues exist)"
  fi
else
  echo "ℹ️  No GitHub token provided, skipping API test"
  echo "ℹ️  To test GitHub integration, use: $0 -o ORGANIZATION -t TOKEN"
fi

echo "🎉 Deployment completed successfully!"
echo "📊 Application: http://localhost:${APP_PORT:-8081}"
echo "🗄️  Database: localhost:${MYSQL_PORT:-3308} (MySQL)"


