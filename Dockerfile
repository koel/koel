FROM php:7-apache
RUN apt-get update \
    && apt-get install apt-utils git -y \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sL https://deb.nodesource.com/setup_5.x | bash - \
    && apt-get install -y nodejs \
    && apt-get install --reinstall zlibc zlib1g zlib1g-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* \
    && docker-php-ext-install mbstring zip pdo pdo_mysql\
    && npm install -g bower gulp
RUN git clone -q https://github.com/phanan/koel .
COPY .env /var/www/html/
RUN adduser --gecos '' --disabled-password koel \
    && chown -R koel /var/www/html \
    && su koel -c 'git config --global url."https://".insteadOf git:// \
    && cd /var/www/html/ && npm install && composer install' \
    && a2enmod rewrite \
    && php artisan key:generate
