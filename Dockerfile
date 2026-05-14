FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    zip unzip git curl libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage
RUN chmod -R 775 storage bootstrap/cache

RUN a2enmod rewrite

COPY .docker/vhost.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80