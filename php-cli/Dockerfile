FROM php:8.2-cli

COPY ./php.ini /usr/local/etc/php/conf.d/php-custom.ini

RUN apt-get update && apt-get install -y \
        nano \
        zip \
        unzip \
        libzip-dev \
        libsqlite3-dev \
        supervisor \
        cron \
        htop \
        && docker-php-ext-install zip \
        && docker-php-ext-install pdo pdo_mysql pcntl \
        && docker-php-ext-install pdo_sqlite

VOLUME /code

RUN pecl install xdebug \
     && docker-php-ext-enable xdebug

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ENV COMPOSER_ALLOW_SUPERUSER 1

# Создание директорий для конфигураций и логов Supervisor
RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor
COPY ./supervisord.conf /etc/supervisor/supervisord.conf

WORKDIR /code

# Указание, что контейнер должен запускаться с Supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]