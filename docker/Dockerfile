FROM php:8.3-fpm

# Instalacja zależności
RUN apt-get update && apt-get install -y \
    libpq-dev libonig-dev libxml2-dev libzip-dev unzip \
    git curl zip \
    librabbitmq-dev libssl-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring xml zip opcache \
    && pecl install amqp && docker-php-ext-enable amqp

# Instalacja Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Konfiguracja PHP
COPY php.ini /usr/local/etc/php/conf.d/php.ini

WORKDIR /var/www/symfony

CMD ["php-fpm"]
