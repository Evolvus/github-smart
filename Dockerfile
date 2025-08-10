# Multi-service app image: Nginx + PHP-FPM + app code

FROM composer:2 AS vendor
WORKDIR /app

# Only copy composer files first for better caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-progress

# Final image
FROM php:8.2-fpm-alpine

ENV APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_MEMORY_LIMIT=256M \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

# Install system deps, nginx, supervisor, and PHP extensions
RUN set -eux; \
    apk add --no-cache bash curl nginx supervisor; \
    docker-php-ext-install pdo pdo_mysql mysqli; \
    mkdir -p /run/nginx

WORKDIR /var/www/html

# Copy app source
COPY . /var/www/html

# Copy vendor from composer stage
COPY --from=vendor /app/vendor /var/www/html/vendor

# Nginx and Supervisor configuration
COPY docker/nginx/default.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Permissions
RUN addgroup -g 1000 -S www && adduser -u 1000 -S www -G www; \
    chown -R www:www /var/www/html; \
    find /var/www/html -type f -exec chmod 0644 {} \; ; \
    find /var/www/html -type d -exec chmod 0755 {} \; ; \
    chmod -R 0775 /var/www/html/logs || true

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]


