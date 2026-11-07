# Stage 1: Builder (pakai Sail untuk build)
FROM sail-8.5/app:latest AS builder

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN npm ci && npm run build

# Stage 2: Runtime (hanya PHP-FPM)
FROM php:8.5-fpm-alpine

RUN apk add --no-cache libpq libzip libpng oniguruma

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl gd zip intl

# Install Redis extension
RUN apk add --no-cache autoconf g++ make && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del autoconf g++ make

COPY --from=builder /var/www/html /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
