FROM php:7-fpm
RUN apt-get update \
    && apt-get install apt-utils git nginx -y \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sL https://deb.nodesource.com/setup_5.x | bash - \
    && apt-get install -y nodejs \
    && apt-get install --reinstall zlibc zlib1g zlib1g-dev \
    && apt-get clean \
    && docker-php-ext-install mbstring zip pdo pdo_mysql\
    && npm install -g bower gulp \
    && rm *.* && git clone -q https://github.com/phanan/koel . \
    && adduser --gecos '' --disabled-password koel \
    && chown -R koel /var/www/html \
    && su koel -c 'git config --global url."https://".insteadOf git:// \
    && cd /var/www/html/ && npm install && composer install' \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /home/koel/.npm /root/.npm
ADD https://gist.githubusercontent.com/NamPNQ/719f40c58995e76a4388/raw /etc/nginx/sites-available/default
RUN chown -R www-data:www-data /var/www/html
CMD service nginx start && php-fpm
