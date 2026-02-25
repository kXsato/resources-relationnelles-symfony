FROM composer:latest AS composer

FROM php:8.4-fpm-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo_mysql \
        zip

RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

COPY usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
