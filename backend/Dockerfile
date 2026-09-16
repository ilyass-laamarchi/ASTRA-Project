# Runtime image for the Laravel API. The Vue application runs in its own service.
FROM php:8.4-cli
RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
# Install production PHP dependencies before copying source to improve rebuild caching.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader --no-scripts
COPY . .
RUN composer dump-autoload --no-dev --optimize && php artisan package:discover --ansi
EXPOSE 8000
# The no-reload server supports multiple local workers using the source copied
# into this image; rebuild the API image after backend source changes.
CMD ["sh", "-c", "php artisan storage:link --force && exec php artisan serve --no-reload --host=0.0.0.0 --port=8000"]
