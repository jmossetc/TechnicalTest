# ── base: shared PHP + extensions ────────────────────────────────────────────
FROM php:8.5-cli-alpine AS base

RUN apk add --no-cache \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ── deps: Composer install (cached layer) ────────────────────────────────────
FROM base AS deps

COPY composer.json composer.lock ./
RUN composer install \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# ── development: includes dev dependencies, source mounted at runtime ─────────
FROM deps AS development

ENV APP_ENV=development

RUN pecl channel-update pecl.php.net \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /tmp/pear

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY . .

CMD ["sleep", "infinity"]

# ── production: no dev dependencies, source baked in ─────────────────────────
FROM base AS production

ENV APP_ENV=production

COPY composer.json composer.lock ./
RUN composer install \
        --no-interaction \
        --no-scripts \
        --no-dev \
        --prefer-dist \
        --optimize-autoloader

COPY src     ./src
COPY public  ./public
COPY database ./database

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
