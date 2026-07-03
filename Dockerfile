FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo_mysql \
        zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY composer.json composer.lock symfony.lock ./

RUN composer install --no-interaction --prefer-dist --no-scripts

COPY . .
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint

RUN chmod +x /usr/local/bin/docker-entrypoint \
    && composer dump-autoload --optimize

ENTRYPOINT ["docker-entrypoint"]
CMD ["apache2-foreground"]
