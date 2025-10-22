# Multi-stage Dockerfile for Laravel Task Management API

# Build stage for Node.js dependencies
FROM node:20-alpine AS node-builder

WORKDIR /var/www/html

# Copy package files
COPY package*.json ./

# Install Node dependencies
RUN npm ci --only=production

# Copy source files and build assets
COPY . .
RUN npm run build

# PHP base stage
FROM php:8.2-fpm-alpine AS php-base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    sqlite \
    mysql-client \
    redis

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    bcmath \
    gd \
    opcache

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Development stage
FROM php-base AS development

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer*.json ./

# Install Composer dependencies
RUN composer install --no-scripts --no-autoloader

# Copy application code
COPY . .

# Copy built assets from node builder
COPY --from=node-builder /var/www/html/public/build ./public/build

# Generate autoloader
RUN composer dump-autoload --optimize

# Create storage and cache directories
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 storage bootstrap/cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Expose port
EXPOSE 9000

# Start PHP-FPM
CMD ["php-fpm"]

# Production stage
FROM php-base AS production

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer*.json ./

# Install Composer dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application code
COPY . .

# Copy built assets from node builder
COPY --from=node-builder /var/www/html/public/build ./public/build

# Generate optimized autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Create storage and cache directories
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 storage bootstrap/cache

# Optimize Laravel for production
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy startup script
COPY docker/scripts/start.sh /start.sh
RUN chmod +x /start.sh

# Expose port
EXPOSE 80

# Start supervisor
CMD ["/start.sh"]