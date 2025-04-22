# Installing Koel on Fedora

This guide will help you set up a Koel server on Fedora, using a precompiled archive. It covers the installation of Koel, Nginx, PHP-FPM, and the configuration of SSL certificates with Let's Encrypt.

--- 

## Overview

In this guide, we will:

- Install Koel using a precompiled archive.
- Set up Nginx with PHP-FPM to serve Koel.
- Configure Let's Encrypt for SSL certificates.

---

## Requirements

- An x86 machine (ARM64 not tested).
- Basic knowledge of the command-line interface.
- An active internet connection. (duh...)
- Music in a file format

---

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
   sudo groupadd koel
   sudo useradd -g koel koel
   ```

## PHP

### PHP installation

**Install Necessary PHP Packages**

   After Fedora is installed, update the system and install PHP-FPM and required PHP extensions:

   TODO: Replace all possible Fedora packages by PHP `composer.phar update`

***First get the right repo***

> [!WARNING]  
> Koel uses (as of writing this) php 8.3 for this we will use
   ```bash
   sudo dnf install https://rpms.remirepo.net/fedora/remi-release-$(rpm -E %fedora).rpm
   sudo dnf module reset php
   sudo dnf module enable php:remi-8.3
   ```

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
     php-pecl-zip `# cannot be installed with composer` \
     php-sodium \
     php-xml \
     -y
   ```

### PHP configuration

Update `/etc/php.ini` to allow larger file uploads:

Example [/etc/php.ini](../../examples/etc/php.ini) 


   ```ini
   upload_max_filesize = 500M
   max_file_uploads = 100
   post_max_size = 500M
   ```

### PHP-FPM Pool configuration

Example [/etc/php-fpm.d/koel.conf](../../examples/etc/php-fpm.d/koel.conf) 

   ```bash
   sudo nano /etc/php-fpm.d/koel.conf

   # remove the default pool
   sudo rm  /etc/php-fpm.d/www.conf

   # start php-fpm
   sudo systemctl start php-fpm.service

   # check the php-fpm pool socket
   ls -lath /var/run/php-fpm/koel.sock
   # check if the permissions are nginx readable
   # srw-rw----. 1 nginx nginx 0 Apr 22 17:16 /var/run/php-fpm/koel.sock
   ```

**Debugging Commands**

- Check the PHP-FPM configuration:

  ```bash
  sudo php-fpm -t
  ```

---

## Nginx and firewall

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

Add your koel user to the nginx group
   ```bash
   sudo usermod -aG nginx koel
   ```

Create the Nginx configuration file for Koel:

Example config can be found here:

[/etc/nginx/nginx.conf](../../examples/etc/nginx/nginx.conf) 

[/etc/nginx/conf.d/koel.example.conf](../../examples/etc/nginx/conf.d/koel.example.conf) 


   ```bash
   # backup the default configuration
   sudo mv /etc/nginx/nginx.conf /etc/nginx/nginx.conf.bak
   # create our own config
   sudo nano /etc/nginx/nginx.conf
   sudo nano /etc/nginx/conf.d/redirect-to-https.conf
   sudo nano /etc/nginx/conf.d/koel.example.conf
   ```
> [!IMPORTANT]
> Don't panic nginx still needs to get the cert before ist will run successfully.

### Configure firewall

   ```bash
   sudo firewall-cmd --permanent --new-zone=WAN
   sudo firewall-cmd --zone=WAN --permanent --add-service=ssh
   sudo firewall-cmd --zone=WAN --permanent --add-service=http
   sudo firewall-cmd --zone=WAN --permanent --add-service=https
   sudo firewall-cmd --reload
   sudo firewall-cmd --zone=WAN --permanent --list-services
   sudo firewall-cmd --zone=WAN --permanent --change-interface=ens3 #here you will need to add your actual interface name
   sudo firewall-cmd --get-default-zone
   sudo firewall-cmd --set-default-zone WAN 
   ```


### Obtain SSL Certificates Using Certbot

   Run Certbot to automatically configure SSL for your Koel domain:

   ```bash
   sudo certbot --nginx -m koel@koel.example -d koel.example.com,www.koel.example.com
   ```

   After successfully obtaining the certificate, you should see:

   ```bash
   Successfully deployed certificate for koel.example to /etc/nginx/conf.d/koel.example.conf
   ```



**Debugging Commands**


- Check the Nginx configuration:

  ```bash
  sudo nginx -t
  ```

---

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

### postgres configuration

***create the database and user***
   ```bash
   sudo -u postgres psql
   ```

   ```psql
   CREATE ROLE koel LOGIN PASSWORD '*************';
   ALTER USER koel WITH PASSWORD '*************';
   CREATE DATABASE koel OWNER koel;
   -- Change the password for PostgreSQL (if used):
   \password postgres
   \q
   ```

> [!WARNING]  
> CHANGE THE '*************' to an actual PASSWORD!!!!

***allow connections on localhost***

Example [/var/lib/pgsql/data/pg_hba.conf](../../examples/var/lib/pgsql/data/pg_hba.conf)

   ```bash
   sudo nano /var/lib/pgsql/data/pg_hba.conf
   ```


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
   sudo mv /srv/koel/koel-7.2.2/{.,}* /srv/koel/www/
   sudo rm -rf /srv/koel/koel-7.2.2
   ```

### Configure Koel Environment

   Most of the environment variables are located in the `.env` file.
   Here is an example file to go from: [/srv/koel/www/.env](../../examples/srv/koel/www/_dot_env)
   ```bash
   sudo nano /srv/koel/www/.env
   ```

### Set Koel file Permissions

Set the appropriate permissions for Koel files and directories:

   ```bash
   sudo chown -R koel:nginx /srv/koel/www /srv/koel/media
   sudo chmod -R 0755 /srv/koel
   sudo find /srv/koel/www -type d -exec chmod 755 {} \;
   sudo find /srv/koel/www -type f -exec chmod 644 {} \;
   ```

### (might not be optional) Set SELinux permissions for the directories:

***for a quick and somewhat secure fix let nginx bypass SELinux***
   ```bash
   semanage permissive -a httpd_t
   ```

***if this does not work we can get even more insecure, letting nginx bypass SELinux***
   ```bash
   setenforce 0
   ```


***WIP actually use SELinux with the correct labeling***

   ```bash
   sudo semanage fcontext -a -t httpd_sys_content_t "/srv/koel/www/(/.*)?"
   sudo restorecon -Rv /srv/koel/www
   # .... here mipht be dragons ....
   sudo semanage fcontext -a -t httpd_var_run_t "/run/php-fpm(/.*)?"
   sudo restorecon -Rv /run/php-fpm
   ```

---


## PHP Composer

### Install Composer

   If Composer is not installed on your system, follow these steps to install it:

   [Get Composer](https://getcomposer.org/download/)

   <details>
     <summary>Click here for more details</summary>

     ```bash
     cd /tmp
     php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
     php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
     php composer-setup.php
     php -r "unlink('composer-setup.php');"
     sudo mv ./composer.phar /usr/local/bin/composer
     sudo chmod +x /usr/local/bin/composer
     ```
   </details>

### Run Composer

   Install and update Koel dependencies using Composer:

   ```bash
   sudo su -l koel
   cd /srv/koel/www
   /srv/koel/www/composer.phar update
   /srv/koel/www/composer.phar install
   ```

## yarn node

***install**
   ```bash
   sudo dnf install yarnpkg
   curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.2/install.sh | bash
   ```

***build the assets***
   ```bash
   sudo su -l koel
   cd /srv/koel/www
   rm -rf node_modules && yarn install && yarn build
   ```

***build some more***
   ```bash
   sudo su -l koel
   cd /srv/koel/www
   npm install
   npm audit fix
   npm install # yes again
   npm run build
   ```

### Run Koel

   ```bash
   sudo su -l koel
   cd /srv/koel/www
   php artisan koel:init --no-assets
   ```

Database connection does not work? Check if you installed 


---

## (Optional) Additional Steps: System Hardening

### Disable Cockpit (a web-based admin interface):

   ```bash
   sudo systemctl disable --now cockpit.socket
   ```

### Enable SSH key auth and disable password auth:

   ```bash
   # From your laptop/workstation/dev machine
   ssh-copy-id -i ~/.ssh/id_ed25519.pub server-admin@koel.example
   # Enter your password
   ####
   # ssh to your koel server
   ssh server-admin@koel.example
   # Edit your sshd config
   sudo nano /etc/ssh/sshd_config
   # Set 
   # PasswordAuthentication no
   # don't forget to uncomment that line.
   ####
   # Restart your sshd server
   sudo systemctl restart sshd.service
   ```


---

## Additional Steps: Quality of Life

Install tmux for session management:

   ```bash
   sudo dnf install tmux -y
   ```

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

- Check database connectivity:
  ```bash
   psql -U koel -p 5432 -h localhost
   ```

---

## Helpful Resources

- [Nginx and PHP-FPM Permissions](https://www.getpagespeed.com/server-setup/nginx-and-php-fpm-what-my-permissions-should-be)
- [PostgreSQL setup on Fedora](https://docs.fedoraproject.org/en-US/quick-docs/postgresql/)
- [Nginx Documentation Search](https://docs.nginx.com/search.html)
- [symfony Documentation](https://symfony.com/doc/6.4/index.html)
