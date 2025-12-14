FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y git unzip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-progress --no-interaction \
    && composer clear-cache \
    && rm -rf var/cache/*

COPY . .

RUN composer dump-autoload --optimize --no-dev

CMD ["php-fpm"]
