FROM mcr.microsoft.com/devcontainers/php:1-8.2-bookworm

# Install system dependencies needed for PHP extensions
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

# Install PHP extensions required by Koel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        exif \
        bcmath \
        xsl \
        pdo_sqlite \
        zip \
        intl

# Set up symlink for web directory
RUN rm -rf /var/www/html

# Create workspace directory and set permissions
WORKDIR /workspaces/koel
