FROM php:8.5.0
WORKDIR /app

COPY . .
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install zip pdo_mysql

RUN /bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
RUN composer install
RUN php artisan key:generate
RUN php artisan migrate:fresh
# RUN php artisan migrate
RUN composer run dev
