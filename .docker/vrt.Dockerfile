FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    freetype freetype-dev \
    libpng libpng-dev \
    libjpeg-turbo libjpeg-turbo-dev \
    git unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && apk del freetype-dev libpng-dev libjpeg-turbo-dev

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
