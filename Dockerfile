# Build stage for PHP dependencies
FROM php:8.1-cli AS vendor

# Install system dependencies and PHP extensions for composer stage
RUN apt-get update && apt-get install -y \
    build-essential \
    libffi-dev \
    bash \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    autoconf \
    gcc \
    g++ \
    make \
    npm \
    libicu-dev \
    libxml2-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libcurl4-openssl-dev \
    libmcrypt-dev \
    libbz2-dev \
    libc-client-dev \
    libkrb5-dev \
    libssl-dev \
    libreadline-dev \
    libsqlite3-dev \
    libtidy-dev \
    libxslt1-dev \
    libgcrypt20-dev \
    libldb-dev \
    libldap2-dev \
    unzip

RUN docker-php-ext-install \
    bcmath \
    dba \
    exif \
    ffi \
    gd \
    gettext \
    intl \
    ldap \
    mysqli \
    opcache \
    pcntl \
    pdo_mysql \
    shmop \
    soap \
    sockets \
    sysvmsg \
    sysvsem \
    sysvshm \
    tidy \
    xsl \
    zip \
    mbstring

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Build stage for frontend assets
FROM node:18 AS frontend
WORKDIR /app
COPY package.json yarn.lock ./
RUN yarn install
COPY . .
RUN yarn build

# Final stage
FROM php:8.1-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    && docker-php-ext-enable \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=frontend /app/public/build /var/www/public/build

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Create storage directory and set permissions
RUN mkdir -p /var/www/storage/framework/{sessions,views,cache} \
    && chown -R www-data:www-data /var/www/storage \
    && chmod -R 775 /var/www/storage

# Copy and set up environment file
COPY .env.example .env
RUN php artisan key:generate

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Start PHP-FPM
CMD ["php", "artisan", "serve", "--host", "0.0.0.0", "--port", "8000"]
