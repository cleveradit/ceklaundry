FROM node:22-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM php:8.4-fpm-alpine AS php-base
RUN apk add --no-cache icu-libs libzip oniguruma \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev oniguruma-dev \
    && docker-php-ext-install pdo_mysql mbstring intl zip pcntl bcmath opcache \
    && apk del .build-deps
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

FROM php-base AS app
COPY . .
RUN --mount=type=cache,id=ceklaundry-composer,target=/tmp/composer-cache COMPOSER_CACHE_DIR=/tmp/composer-cache composer install --no-interaction --prefer-dist --optimize-autoloader \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /app/public/build /app/public/build
COPY docker/cron/crontab /etc/crontabs/root
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint
ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

FROM nginx:alpine AS web
COPY docker/web/default.conf /etc/nginx/conf.d/default.conf
COPY public /app/public
COPY --from=frontend /app/public/build /app/public/build
