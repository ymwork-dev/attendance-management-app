FROM php:8.2-fpm-alpine

# 必要なツールとライブラリのインストール
RUN apk update && apk add --no-cache \
    bash \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    libzip-dev

# PHP拡張機能のインストール（MySQL接続や画像処理用）
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip

# 独自のphp.ini設定を適用
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

# Composer（PHPのパッケージ管理ツール）をインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN addgroup -g 1000 app \
    && adduser -D -u 1000 -G app app

ENV HOME=/home/app

WORKDIR /var/www/html

USER app
