# Installing Koel on Fedora

This guide will help you set up a Koel server on Fedora, using a precompiled archive. It covers the installation of Koel, Nginx, PHP-FPM, and the configuration of SSL certificates with Let's Encrypt.

## Overview

In this guide, we will:

- Install Koel using a precompiled archive.
- Set up Nginx with PHP-FPM to serve Koel.
- Configure Let's Encrypt for SSL certificates.

## Requirements

- An x86 machine (ARM64 not tested).
- Basic knowledge of the command-line interface.
- An active internet connection. (duh...)

## Server Setup

**Install Fedora on Your Server**

   For this guide, we will use the Fedora Server 42 Network Install ISO.

   <details>
     <summary>Click here for more details</summary>

     - You can download the ISO image from the [Fedora project website](https://fedoraproject.org/server/download).
     - Follow the [Fedora server installation guide](https://docs.fedoraproject.org/en-US/fedora-server/installation/).

   </details>

---

**Creat an Application User for the Application**


   Create a `koel` user and group
   ```bash
   groupadd koel
   useradd -g koel koel
   sudo usermod -aG nginx koel
   ```

## PHP

### PHP installation

2. **Install Necessary PHP Packages**

   After Fedora is installed, update the system and install PHP-FPM and required PHP extensions:

   TODO: Replace all possible Fedora packages by PHP `composer.phar update`

   ```bash
   sudo dnf update
   sudo dnf install \
     php-bcmath \
     php-cli \
     php-common \
     php-ctype \
     php-curl \
     php-dom \
     php-fpm \
     php-gd \
     php-intl \
     php-mbstring \
     php-pdo \
     php-pecl-zip # cannot be installed with composer
     php-sodium \
     php-xml \
     -y
   ```

### PHP configuration

Update `/etc/php.ini` to allow larger file uploads:

```ini
upload_max_filesize = 500M
post_max_size = 500M
```

---

**Configure PHP-FPM**


 and grant permissions for the necessary directories:

   ```bash
   sudo chown -R koel:nginx /srv/koel/www/public
   sudo chown -R koel:nginx /srv/koel/media
   sudo systemctl start php-fpm.service
   ```

**Debugging Commands**

- Check the PHP-FPM configuration:

  ```bash
  sudo php-fpm -t
  ```

## Nginx

### Install Nginx and Let's Encrypt (Certbot)

   Install Nginx and Certbot for SSL management:

   ```bash
   sudo dnf update
   sudo dnf install \
     certbot \
     nginx \
     python3-certbot-nginx \
     -y
   ```

### Nginx Configuration

Create the Nginx configuration file for Koel:

Example config can be found at:

[/etc/nginx/conf.d/koel.example.conf](../../examples/etc/nginx/conf.d/koel.example.conf) 

[/etc/nginx/nginx.conf](../../examples/etc/nginx/nginx.conf) 

---

### Obtain SSL Certificates Using Certbot

   Run Certbot to automatically configure SSL for your Koel domain:

   ```bash
   sudo certbot --nginx -m koel@koel.example -d koel.example.com,www.koel.example.com
   ```

   After successfully obtaining the certificate, you should see:

   ```bash
   Successfully deployed certificate for koel.example to /etc/nginx/conf.d/koel.example.conf
   ```

---

**Debugging Commands**


- Check the Nginx configuration:

  ```bash
  sudo nginx -t
  ```

## postgres

### postgres installation

   If you plan to use PostgreSQL, install it and set up a Koel database:

   ```bash
   sudo dnf update
   sudo dnf install \
     postgresql-server \
     postgresql-contrib \
     php-pgsql \
     -y

   sudo systemctl enable postgresql
   sudo postgresql-setup --initdb --unit postgresql
   sudo systemctl start postgresql
   ```

### postgres installation

   ```bash
   sudo -u postgres psql
   ```

   ```psql
   CREATE ROLE koel LOGIN PASSWORD '*************';
   CREATE DATABASE koel OWNER koel;
   ```

> [!WARNING]  
> CHANGE THE '*************' to an actual PASSWORD!!!!


## ffmpeg


**Install ffmpeg and link**

   ```bash
   sudo dnf install ffmpeg-free -y
   sudo ln -s /usr/bin/ffmpeg /usr/local/bin/ffmpeg
   ```
> [!NOTE]  
> you can instead change the location of ffmpeg in the 
> [/srv/koel/www/.env](../../examples/srv/koel/www/_dot_env) file 


## Koel

### Configure Koel Environment

   Most of the environment variables are located in the `.env` file.
   Here is an example file to go from: [/srv/koel/www/.env](../../examples/srv/koel/www/_dot_env)

---

### Set Up Koel Directories


In this guide we will have three main Folders for koel. All of them will be nested in /srv/koel

#### media

`/srv/koel/media`

This folder is used for storing the music files. You can upload them directly to this folder on your server
After uploading you will need to run `php artisan koel:sync` as the `koel` user from `/srv/koel/www`


#### www

`/srv/koel/www`

This folder is used for 

#### releases

`/srv/koel/releases`

This folder is used to download koel, you can find a .zip or .tar.gz on the [Releases page](https://github.com/koel/koel/releases) 


   Create the necessary directories, download Koel, and set the correct permissions:

   ```bash
   sudo mkdir -p /srv/koel/www /srv/koel/media /srv/koel/releases
   sudo chown -R "$USER" /srv/koel/releases
   cd /srv/koel/releases
   curl https://codeload.github.com/koel/koel/zip/refs/tags/v7.2.2 -o koel-v7.2.2.zip
   sudo unzip ./koel-v7.2.2.zip -d /srv/koel/
   cd /srv/koel/
   sudo mv koel-7.2.2/* www/

   sudo chown -R koel:nginx /srv/koel/www /srv/koel/media
   sudo chmod -R 0755 /srv/koel



   ```

---

**Set Permissions**

   Set the appropriate permissions for Koel files and directories:

   ```bash
   sudo find /srv/koel/www -type d -exec chmod 755 {} \;
   sudo find /srv/koel/www -type f -exec chmod 644 {} \;
   ```

   Set SELinux permissions for the directories:

   ```bash
   sudo semanage fcontext -a -t httpd_sys_content_t "/srv/koel/www/public(/.*)?"
   sudo restorecon -Rv /srv/koel/www/public

   sudo semanage fcontext -a -t httpd_var_run_t "/run/php-fpm(/.*)?"
   sudo restorecon -Rv /run/php-fpm
   ```

---



**Install Composer**

   If Composer is not installed on your system, follow these steps to install it:

   [Get Composer](https://getcomposer.org/download/)

   <details>
     <summary>Click here for more details</summary>

     ```bash
     cd /srv/koel/www
     php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
     php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
     php composer-setup.php
     php -r "unlink('composer-setup.php');"
     ```
   </details>

---

**Run Composer**

   Install and update Koel dependencies using Composer:

   ```bash
   cd /srv/koel/www
   composer install
   composer update
   ```

---


**Run Koel**

   ```bash
   sudo su -l koel
   cd /srv/koel/www
   php artisan koel:init --no-assets
   ```


---


**Initialize Koel**

   Initialize Koel and set it up:

   ```bash
   php artisan koel:init --no-assets
   ```

---




## Additional Steps: System Hardening

Disable Cockpit (a web-based admin interface):

```bash
sudo systemctl disable --now cockpit.socket
```

Change the password for PostgreSQL (if used):

```bash
sudo -u postgres psql
\password postgres
```

---

## Additional Steps: Quality of Life

Install tmux for session management:

```bash
sudo dnf install tmux -y
```

---

### Debugging Commands


- Check the Nginx configuration:

  ```bash
  sudo nginx -t
  ```

- Clear the application cache and config:

  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan config:cache
  ```


- Reload PHP-FPM:

  ```bash
  sudo systemctl reload php-fpm.service
  ```

- Reload Nginx:

  ```bash
  sudo systemctl reload nginx.service
  ```

- Check the Koel directory:

  ```bash
  sudo -u nginx stat /srv/koel/www/public
  ```

---

## Helpful Resources

- [Nginx and PHP-FPM Permissions](https://www.getpagespeed.com/server-setup/nginx-and-php-fpm-what-my-permissions-should-be)
- [PostgreSQL setup on Fedora](https://docs.fedoraproject.org/en-US/quick-docs/postgresql/)
- [Nginx Documentation Search](https://docs.nginx.com/search.html)
- [symfony Documentation](https://symfony.com/doc/6.4/index.html)
