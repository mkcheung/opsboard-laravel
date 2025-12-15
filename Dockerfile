FROM php:8.4-fpm

# System deps + PHP extensions for Postgres
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
  && docker-php-ext-install pdo pdo_pgsql zip \
  && rm -rf /var/lib/apt/lists/*

RUN pecl install pcov \
  && docker-php-ext-enable pcov \
  && printf "pcov.enabled=1\npcov.directory=/var/www\n" > /usr/local/etc/php/conf.d/pcov.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www