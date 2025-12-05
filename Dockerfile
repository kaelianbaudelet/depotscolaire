# syntax=docker/dockerfile:1.4

FROM php:8.2-apache AS symfony_base

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        curl \
        libicu-dev \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libpq-dev \
        libxslt1.1 \
        libxslt1-dev \
        mariadb-client \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) intl pdo_mysql opcache zip \
    && pecl install apcu \
    && docker-php-ext-enable apcu \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/default-ssl.conf \
    && printf "<Directory %s>\n    AllowOverride All\n    Require all granted\n</Directory>\n" "${APACHE_DOCUMENT_ROOT}" > /etc/apache2/conf-available/symfony.conf \
    && a2enconf symfony \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

FROM symfony_base AS symfony_dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/docker-entrypoint-dev.sh /usr/local/bin/docker-entrypoint-dev.sh
COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/conf.d/app.dev.ini /usr/local/etc/php/conf.d/app.dev.ini
RUN chmod +x /usr/local/bin/docker-entrypoint-dev.sh

ENV APP_ENV=dev

ENTRYPOINT ["/usr/local/bin/docker-entrypoint-dev.sh"]
CMD ["apache2-foreground"]

FROM symfony_base AS symfony_builder

COPY composer.json composer.lock symfony.lock* ./
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction

COPY . .

RUN mkdir -p var \
    && composer dump-autoload --classmap-authoritative --no-dev \
    && chown -R www-data:www-data var public

FROM symfony_base AS symfony_prod

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/app.ini
COPY --from=symfony_builder /var/www/html /var/www/html

ENV APP_ENV=prod \
    APP_DEBUG=0

CMD ["apache2-foreground"]
