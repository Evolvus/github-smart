#!/usr/bin/env bash
set -euo pipefail

IMAGE="ghcr.io/evolvus/github-smart:latest"
APP_NAME="github-smart-app"
MYSQL_NAME="github-smart-mysql"



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
  -v github-smart-logs:/var/www/html/logs \
  --link ${MYSQL_NAME}:mysql \
  "$IMAGE"

echo "App deployed and listening on port ${APP_PORT:-8081}"


