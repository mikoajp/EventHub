FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN echo "xdebug.mode=off" >> /usr/local/etc/php/conf.d/xdebug.ini

WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --optimize-autoloader
RUN chown -R www-data:www-data /var/www/html

CMD ["php-fpm"]