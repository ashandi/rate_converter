FROM php:8.1-fpm

RUN apt-get update \
    && apt-get install -y zip unzip libzip-dev default-mysql-client \
    && docker-php-ext-install zip pdo_mysql

WORKDIR /app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
