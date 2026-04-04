# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 – Composer dependencies
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --optimize-autoloader \
    --prefer-dist

# ---------------------------------------------------------------------------
# Stage 2 – Production image
# ---------------------------------------------------------------------------
FROM php:8.2-fpm-alpine

# Install system packages and PHP extensions required by CodeIgniter 4
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        libzip-dev \
        icu-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        intl \
        mysqli \
        pdo_mysql \
        zip \
        opcache \
    && rm -rf /var/cache/apk/*

# Copy custom PHP & Nginx configuration
COPY docker/php.ini          /usr/local/etc/php/conf.d/app.ini
COPY docker/nginx.conf       /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

# Copy application source
COPY --chown=www-data:www-data . .

# Copy vendor from Stage 1
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor

# Ensure writable directories exist and are owned by www-data
RUN mkdir -p writable/cache writable/logs writable/session writable/uploads \
    && chown -R www-data:www-data writable \
    && chmod -R 775 writable

EXPOSE 80

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
