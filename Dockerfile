# Multi-stage build for production
FROM composer:2.6 AS composer

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Production stage
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    zip \
    gd \
    curl \
    xml

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Copy vendor directory from composer stage
COPY --from=composer /app/vendor ./vendor

# Create logs directory if it doesn't exist
RUN mkdir -p /var/www/html/logs

# Create config log file and set proper permissions (as root)
RUN touch /var/www/html/config/app.log \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/logs \
    && chmod 666 /var/www/html/config/app.log

# Configure Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Enable mod_rewrite
RUN a2enmod rewrite

# Security: Disable Apache version and server info
RUN echo "ServerTokens Prod" >> /etc/apache2/apache2.conf \
    && echo "ServerSignature Off" >> /etc/apache2/apache2.conf

# Create non-root user for security
RUN useradd -m -s /bin/bash appuser \
    && usermod -a -G www-data appuser

# Configure Apache to run as non-root user
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf

# Expose port 8080 (non-privileged port)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:8080/ || exit 1

# Start Apache as non-root user
USER appuser
CMD ["apache2-foreground"] 