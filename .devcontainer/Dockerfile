FROM mcr.microsoft.com/devcontainers/php:1-8.2-bookworm

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libxslt1-dev \
    libzip-dev \
    libsqlite3-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        exif \
        bcmath \
        xsl \
        pdo_sqlite \
        zip \
        intl

RUN rm -rf /var/www/html

WORKDIR /workspaces/koel
