FROM php:7.4-fpm-alpine3.13

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
    openssl-dev && \
    docker-php-ext-install sockets pcntl zip && \
    pecl install event ev && \
    docker-php-ext-enable opcache ev

COPY ./event.ini /usr/local/etc/php/conf.d/

EXPOSE 8000 8001 8002
VOLUME /var/www
WORKDIR /var/www
