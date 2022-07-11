FROM php:8.1-fpm
WORKDIR /app

RUN apt-get update \
    && apt-get install -y \
        git \
        mariadb-client \
        vim \
    && pecl install \
        xdebug \
    && docker-php-ext-install \
        pdo_mysql \
    && docker-php-ext-enable \
        xdebug

COPY ./composer.json ./
COPY ./composer.lock ./
COPY ./composer /usr/local/bin/composer
RUN composer install --no-dev