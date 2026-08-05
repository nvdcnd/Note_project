FROM php:8.5.0
WORKDIR /app

RUN composer install
RUN php artisan key:generate
RUN php artisan migrate:fresh
# RUN php artisan migrate
RUN composer run dev