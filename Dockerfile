# Stage 1: Builder
FROM php:8.5-fpm AS builder

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev libzip-dev \
    libpq-dev libicu-dev zip unzip nodejs npm

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip intl

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction

COPY package.json package-lock.json ./

RUN npm ci

COPY . .

RUN composer dump-autoload --optimize --no-dev

RUN npm run build

# Stage 2: Runtime
FROM php:8.5-fpm-alpine

RUN apk add --no-cache libpq libzip libpng oniguruma libintl icu-libs

RUN apk add --no-cache --virtual .build-deps autoconf g++ make && \
    docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip intl && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del .build-deps

WORKDIR /var/www/html

COPY --from=builder /var/www/html /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
