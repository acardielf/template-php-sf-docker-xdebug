FROM php:8.5-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    libpq-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    intl \
    opcache \
    pcntl \
    bcmath \
    mbstring

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Xdebug (only for development)
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy PHP configuration
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY .docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Create non-root user
RUN addgroup -g 1000 -S appgroup && \
    adduser -u 1000 -S appuser -G appgroup

# ---------------------------------------------------
FROM base AS development

# Switch to non-root user
USER appuser

# ---------------------------------------------------
FROM base AS production

# Remove Xdebug in production
RUN rm -f /usr/local/etc/php/conf.d/xdebug.ini && \
    docker-php-ext-disable xdebug || true

COPY --chown=appuser:appgroup . /var/www/html

USER appuser

RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress
