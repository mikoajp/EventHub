# syntax=docker/dockerfile:1.6
FROM php:8.3-apache AS base

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    wget \
    vim \
    libpq-dev \
    librabbitmq-dev \
    libzip-dev \
    libicu-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    autoconf \
    pkg-config \
    build-essential \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        intl \
        xml \
        mbstring \
        gd \
        opcache \
        bcmath \
	sockets

RUN pecl install redis amqp \
    && docker-php-ext-enable redis amqp

# Composer in a separate stage to leverage cache
FROM composer:2 AS composer

FROM base AS build
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'realpath_cache_size=4096K'; \
    echo 'realpath_cache_ttl=600'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
    echo 'memory_limit=512M'; \
    echo 'upload_max_filesize=50M'; \
    echo 'post_max_size=50M'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_vars=3000'; \
    echo 'date.timezone=Europe/Warsaw'; \
    } > /usr/local/etc/php/conf.d/custom.ini

RUN a2enmod rewrite headers expires deflate

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
    \n\
    # Symfony FallbackResource - wszystko przez index.php\n\
    FallbackResource /index.php\n\
    \n\
    # Security headers\n\
    Header always set X-Content-Type-Options nosniff\n\
    Header always set X-Frame-Options DENY\n\
    Header always set X-XSS-Protection "1; mode=block"\n\
    \n\
    # Compression\n\
    <IfModule mod_deflate.c>\n\
        AddOutputFilterByType DEFLATE text/plain\n\
        AddOutputFilterByType DEFLATE text/html\n\
        AddOutputFilterByType DEFLATE text/xml\n\
        AddOutputFilterByType DEFLATE text/css\n\
        AddOutputFilterByType DEFLATE application/xml\n\
        AddOutputFilterByType DEFLATE application/xhtml+xml\n\
        AddOutputFilterByType DEFLATE application/rss+xml\n\
        AddOutputFilterByType DEFLATE application/javascript\n\
        AddOutputFilterByType DEFLATE application/x-javascript\n\
    </IfModule>\n\
    \n\
    # Cache static files\n\
    <IfModule mod_expires.c>\n\
        ExpiresActive on\n\
        ExpiresByType image/png "access plus 1 month"\n\
        ExpiresByType image/jpg "access plus 1 month"\n\
        ExpiresByType image/jpeg "access plus 1 month"\n\
        ExpiresByType image/gif "access plus 1 month"\n\
        ExpiresByType text/css "access plus 1 month"\n\
        ExpiresByType application/pdf "access plus 1 month"\n\
        ExpiresByType text/javascript "access plus 1 month"\n\
        ExpiresByType application/javascript "access plus 1 month"\n\
    </IfModule>\n\
</Directory>\n\
\n\
# Health check endpoint\n\
<Location "/health">\n\
    SetHandler application/x-httpd-php\n\
</Location>' > /etc/apache2/conf-available/eventhub.conf

RUN a2enconf eventhub

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

# Use BuildKit cache mounts for Composer
RUN --mount=type=cache,target=/root/.composer/cache composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy source late to maximize layer cache
COPY . /var/www/html/

RUN composer dump-autoload --optimize --classmap-authoritative

RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 var/

RUN echo '<?php\n\
header("Content-Type: application/json");\n\
\n\
$health = [\n\
    "status" => "ok",\n\
    "timestamp" => date("c"),\n\
    "checks" => [\n\
        "php" => phpversion(),\n\
        "extensions" => [\n\
            "pdo_pgsql" => extension_loaded("pdo_pgsql"),\n\
            "redis" => extension_loaded("redis"),\n\
            "amqp" => extension_loaded("amqp"),\n\
            "opcache" => extension_loaded("opcache")\n\
        ]\n\
    ]\n\
];\n\
\n\
// Check if all required extensions are loaded\n\
$required = ["pdo_pgsql", "redis", "amqp"];\n\
foreach ($required as $ext) {\n\
    if (!extension_loaded($ext)) {\n\
        http_response_code(503);\n\
        $health["status"] = "error";\n\
        $health["error"] = "Missing required extension: " . $ext;\n\
        break;\n\
    }\n\
}\n\
\n\
echo json_encode($health, JSON_PRETTY_PRINT);\n\
?>' > /var/www/html/public/health.php

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --retries=3 --start-period=60s \
    CMD curl -f http://localhost/health.php | grep -q '"status":"ok"' || exit 1

FROM base AS runtime

# Copy built app from build stage
COPY --from=build /var/www/html /var/www/html

CMD ["apache2-foreground"]