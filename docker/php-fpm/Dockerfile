FROM php:7.3-fpm

RUN apt-get update && buildDeps="libpq-dev libzip-dev git nano wget libpng-dev" && apt-get install -y $buildDeps --no-install-recommends
RUN docker-php-ext-install pdo pdo_mysql zip bcmath gd calendar

RUN wget https://getcomposer.org/composer.phar && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer

WORKDIR /app
