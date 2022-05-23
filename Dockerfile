FROM php:7.4-fpm-alpine

RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories && \
    apk update && \
    apk add --no-cache \
    autoconf \
    build-base \
    composer \
    e2fsprogs-dev \
    libzip-dev \
    libevent-dev \
    openssl-dev && \
    docker-php-ext-install sockets pcntl zip && \
    pecl install event && \
    docker-php-ext-enable opcache \

COPY ./event.ini /usr/local/etc/php/conf.d/

EXPOSE 8000 8001 8002
VOLUME /var/www
WORKDIR /var/www
