FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.4-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev unzip \
    && docker-php-ext-install pdo_pgsql zip \
    && a2enmod rewrite \
    && sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=assets /app/public/build ./public/build
COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/campus-reserve-entrypoint

RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && chmod +x /usr/local/bin/campus-reserve-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/campus-reserve-entrypoint"]
CMD ["sh", "-c", "php artisan migrate --force && php artisan storage:link --force && php artisan config:cache && (php artisan schedule:work --no-interaction >/proc/1/fd/1 2>&1 &) && (php artisan queue:work --sleep=3 --tries=3 --timeout=60 --no-interaction >/proc/1/fd/1 2>&1 &) && apache2-foreground"]
