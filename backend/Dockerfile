# syntax=docker/dockerfile:1.7

FROM php:8.3-apache AS base

RUN apt-get update && apt-get install -y \
    git unzip curl wget \
    libpq-dev librabbitmq-dev libzip-dev libicu-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql zip intl xml mbstring gd \
        opcache bcmath sockets

RUN pecl install redis amqp && docker-php-ext-enable redis amqp

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
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    } > /usr/local/etc/php/conf.d/custom.ini

RUN a2enmod rewrite headers expires deflate

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN printf '<Directory /var/www/html/public>\nOptions -Indexes +FollowSymLinks\nAllowOverride All\nRequire all granted\nFallbackResource /index.php\n</Directory>\n' > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony

# Enable Authorization header pass-through for JWT authentication
RUN printf '<IfModule mod_rewrite.c>\\nRewriteEngine On\\nRewriteCond %%{HTTP:Authorization} ^(.*)\\nRewriteRule .* - [e=HTTP_AUTHORIZATION:%%1]\\n</IfModule>\\n' > /etc/apache2/conf-available/auth-header.conf \
    && a2enconf auth-header

FROM composer:2 AS composer-stage

FROM base AS builder

WORKDIR /var/www/html

COPY --from=composer-stage /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock* ./

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative

RUN mkdir -p var/cache var/log var/sessions public/uploads \
    && chown -R www-data:www-data . \
    && chmod -R 755 . \
    && chmod -R 777 var

RUN echo '<?php http_response_code(200); echo json_encode(["status"=>"healthy","timestamp"=>time()]);' > public/health.php

FROM base AS runtime

WORKDIR /var/www/html

COPY --from=builder --chown=www-data:www-data /var/www/html /var/www/html

HEALTHCHECK --interval=20s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

EXPOSE 80

CMD ["apache2-foreground"]