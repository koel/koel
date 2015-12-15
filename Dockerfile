FROM php:5.6-apache
RUN apt-get update \
    && apt-get install apt-utils git -y \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && curl -sL https://deb.nodesource.com/setup_5.x | bash - \
    && apt-get install -y nodejs
RUN apt-get install --reinstall zlibc zlib1g zlib1g-dev \
    && docker-php-ext-install mbstring zip pdo pdo_mysql\
    && npm install -g bower gulp
COPY . /var/www/html/
RUN mkdir -p /var/www/ && chown -R www-data:www-data /var/www/
RUN chsh -s /bin/bash www-data
RUN su - www-data -c 'git config --global url."https://".insteadOf git://'
RUN su - www-data -c 'cd /var/www/html/ && npm install && composer install'
RUN su root
RUN chsh -s /usr/sbin/nologin www-data
ENV APACHE_LOG_DIR /var/log/apache2
ADD apache.conf /etc/apache2/sites-available/000-default.conf
RUN /bin/ln -sf /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod rewrite
RUN a2enmod headers
RUN php artisan key:generate
