# Install PHP dependencies
FROM composer:2 AS dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

FROM composer:2 AS dev-dependencies
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Generate Laravel Wayfinder output
FROM php:8.4-cli-alpine AS wayfinder
WORKDIR /app
RUN apk add --no-cache oniguruma-dev libzip-dev sqlite-dev icu-dev \
    && docker-php-ext-install pdo_sqlite mbstring bcmath intl
COPY --from=dev-dependencies /app/vendor ./vendor
COPY app ./app
COPY routes ./routes
COPY config ./config
COPY bootstrap ./bootstrap
COPY artisan composer.json composer.lock .env.example ./
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache resources/js \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && php artisan wayfinder:generate --with-form

# Build frontend assets
FROM php:8.4-cli-alpine AS frontend
WORKDIR /app
RUN apk add --no-cache nodejs npm oniguruma-dev libzip-dev sqlite-dev icu-dev \
    && docker-php-ext-install pdo_sqlite mbstring bcmath intl
COPY --from=dev-dependencies /app/vendor ./vendor
COPY --from=wayfinder /app/resources/js ./resources/js
COPY resources/js ./resources/js
COPY app ./app
COPY routes ./routes
COPY config ./config
COPY bootstrap ./bootstrap
COPY artisan composer.json composer.lock .env.example ./
COPY package.json package-lock.json ./
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && cp .env.example .env \
    && php artisan key:generate --force \
    && npm ci
COPY resources/css ./resources/css
COPY public ./public
COPY vite.config.ts tsconfig.json components.json .npmrc ./
RUN npm run build

# Runtime
FROM php:8.4-fpm-alpine AS runtime
WORKDIR /var/www/html
RUN apk add --no-cache nginx supervisor oniguruma-dev libzip-dev sqlite-dev icu-dev \
    && docker-php-ext-install pdo_sqlite mbstring bcmath intl opcache \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=dependencies /app/vendor ./vendor
COPY . .
COPY --from=frontend /app/public/build ./public/build
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh \
    && chown -R www-data:www-data /var/www/html
EXPOSE 80
ENTRYPOINT ["/usr/local/bin/start.sh"]
