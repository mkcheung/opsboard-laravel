FROM php:8.4-fpm

# System deps + PHP extensions for Postgres
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev \
  && docker-php-ext-install pdo pdo_pgsql zip \
  && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www