# syntax=docker/dockerfile:1.7
FROM php:8.3-apache AS base

RUN apt-get update && apt-get install -y \
    git unzip curl wget vim \
    libpq-dev librabbitmq-dev libzip-dev libicu-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    autoconf pkg-config build-essential \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) pdo pdo_pgsql pgsql zip intl xml mbstring gd opcache bcmath sockets

RUN pecl install redis && docker-php-ext-enable redis # removed amqp ext to avoid compat warning

FROM composer:2 AS composer

FROM base AS build
COPY --from=composer /usr/bin/composer /usr/bin/composer

# PHP Config
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

RUN { \
    echo 'memory_limit=512M'; \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
    echo 'max_execution_time=300'; \
    echo 'date.timezone=Europe/Warsaw'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Enable Apache modules
RUN a2enmod rewrite headers expires deflate

# Configure Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN printf '<Directory /var/www/html/public>\nOptions -Indexes +FollowSymLinks\nAllowOverride All\nRequire all granted\nFallbackResource /index.php\n</Directory>\n' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Set working directory
WORKDIR /var/www/html

# Install Composer dependencies
COPY composer.json composer.lock symfony.lock* ./
RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy application code
COPY . .

# Optimize autoloader
RUN composer dump-autoload --optimize --classmap-authoritative

# Setup permissions
RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data . \
    && chmod -R 755 . \
    && chmod -R 777 var

RUN printf 'DirectoryIndex index.php\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase /\nRewriteCond %%{REQUEST_FILENAME} !-f\nRewriteCond %%{REQUEST_FILENAME} !-d\nRewriteRule ^ index.php [QSA,L]\n</IfModule>\n<IfModule !mod_rewrite.c>\nFallbackResource /index.php\n</IfModule>\n' > public/.htaccess

# Create health check endpoint
RUN echo '<?php http_response_code(200); echo json_encode(["status"=>"ok"]);' > public/health.php

# Default env vars
ENV REDIS_CACHE_ENABLED=true

# Healthcheck
HEALTHCHECK --interval=20s --timeout=5s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

EXPOSE 80

# Runtime stage
FROM base AS runtime

# Copy Apache config from build stage
COPY --from=build /etc/apache2/conf-available/symfony.conf /etc/apache2/conf-available/symfony.conf
COPY --from=build /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf

# Enable modules and config
RUN a2enmod rewrite headers expires deflate \
    && a2enconf symfony

# Set DocumentRoot env
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Copy application
COPY --from=build /var/www/html /var/www/html

WORKDIR /var/www/html

CMD ["apache2-foreground"]