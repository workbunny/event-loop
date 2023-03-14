FROM php:8.0-fpm-alpine

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories && \
    apk update && \
    apk add --no-cache \
    autoconf \
    build-base \
    e2fsprogs-dev \
    libzip-dev \
    unzip \
    libevent-dev \
    libev-dev \
    openssl-dev \
    composer && \
    composer self-update && \
    docker-php-ext-install sockets pcntl zip && \
    pecl install event ev && \
    docker-php-ext-enable opcache ev \

COPY ./event.ini /usr/local/etc/php/conf.d/

VOLUME /var/www
WORKDIR /var/www