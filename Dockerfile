FROM php:8.1-zts-alpine

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
    pecl install parallel event && \
    docker-php-ext-enable opcache parallel \

COPY ./event.ini /usr/local/etc/php/conf.d/

# 安装GD
#RUN apk add --no-cache \
#    libpng-dev \
#    libwebp-dev \
#    libjpeg-turbo-dev \
#    freetype-dev && \
#    docker-php-ext-configure gd \
#    --with-jpeg=/usr/include/ \
#    --with-freetype=/usr/include/ && \
#    docker-php-ext-install gd

EXPOSE 8000 8001 8002
VOLUME /var/www
WORKDIR /var/www
