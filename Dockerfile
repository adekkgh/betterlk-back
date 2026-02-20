FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    git \
    curl \
    libpq-dev \
    zip \
    unzip

RUN docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www

CMD ["php-fpm"]
