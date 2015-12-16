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
COPY . /var/www/html/
RUN mkdir -p /var/www/ \
    && chown -R www-data:www-data /var/www/ \
    && chsh -s /bin/bash www-data
RUN su - www-data -c 'git config --global url."https://".insteadOf git:// \
    && cd /var/www/html/ && npm install && composer install'
RUN chsh -s /usr/sbin/nologin www-data
RUN a2enmod rewrite
RUN php artisan key:generate
