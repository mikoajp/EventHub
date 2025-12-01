# syntax=docker/dockerfile:1.7

FROM dunglas/frankenphp:latest-php8.3 AS base

RUN apt-get update && apt-get install -y \
    git unzip curl wget \
    libpq-dev librabbitmq-dev libzip-dev libicu-dev libxml2-dev \
    libpng-dev libjpeg-dev libfreetype6-dev libonig-dev \
    autoconf pkg-config build-essential \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo pdo_pgsql pgsql zip intl xml mbstring gd \
        opcache bcmath sockets

RUN install-php-extensions redis amqp

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

ENV SERVER_NAME=:80

HEALTHCHECK --interval=20s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

EXPOSE 80 443

RUN echo '{\n\
    order php_server before file_server\n\
}\n\
\n\
:80 {\n\
    root * /var/www/html/public\n\
    encode gzip\n\
    php_server\n\
    file_server\n\
}\n' > /etc/caddy/Caddyfile

ENTRYPOINT ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]