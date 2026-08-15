FROM php:8.3-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && a2enmod rewrite \
    && sed -ri "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'upload_max_filesize = 20M'; \
        echo 'post_max_size = 24M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts

COPY . .

RUN composer dump-autoload --no-dev --optimize

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache database \
    && chown -R www-data:www-data storage bootstrap/cache database

EXPOSE 80
