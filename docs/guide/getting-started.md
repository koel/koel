# Getting Started

## Requirements

Koel consists of two parts: the server and the client. The server is a Laravel application acting as the API, and the client is a Vue.js application responsible for the user interface.
The requirements for each part are as follows:

### Server

* [All requirements by Laravel](https://laravel.com/docs/10.x/deployment#server-requirements) – PHP >= 8.1 with required extensions
* Any database supported by Laravel – MySQL, MariaDB, PostgreSQL, or SQLite
* If you're [building Koel from source](#building-from-source), make sure to have [Composer](https://getcomposer.org/), Git, and Node.js >= 14 with [Yarn](https://yarnpkg.com).

### Client

* Koel has been tested on Chrome 47, Firefox 42, Safari 8, Opera 34, and Edge, but having the latest version is always recommended.

## Installation

There are three methods to install and start using Koel:

### Using a Pre-Compiled Archive

Koel supports installing from a pre-compiled archive, which eliminates the need of manually compiling the front-end assets.

First, go to the [Releases page](https://github.com/koel/koel/releases) on GitHub, download either the `.tar.gz` or `.zip` file found under "Assets," and unzip it into the destination web root directory. From there, run the two following commands:

```bash
php artisan koel:init --no-assets

# Follow the wizard to populate necessary configurations

php artisan serve
```

### Building from Source

From your console, run the following commands:

```bash
cd <KOEL_ROOT_DIR>
git clone https://github.com/koel/koel.git .
git checkout latest # Check out the latest version at https://github.com/koel/koel/releases
composer install
php artisan koel:init

# Follow the wizard to populate necessary configurations

php artisan serve
```

In both cases, you should now be able to visit http://localhost:8000 in your browser and start using Koel.

::: warning Use a proper webserver
http://localhost:8000 is only the _development_ server for Koel (or rather, Laravel).
For optimal performance, you'll want to set up a production server (Apache, nginx, Caddy etc.) and point it to the `public` directory of Koel.
Koel provides a sample configuration for nginx in `nginx.conf.example`,
but the process shouldn't be any different from that of a standard PHP application.
:::

### Using Docker

Koel has an official Docker image: [koel/docker](https://github.com/koel/docker). Please refer to the repository for detailed instructions and issue reporting.

## Configuration

Koel’s configuration is stored in the `.env` file at the root of the project, which is created during the installation process
by copying the `.env.example` file and filling it with sensible values.
You can always modify the values to suit your environment.

### Configure a Mailer

Though Koel can work without a mailer, certain features like "forgot password" and user invitation require a mailer to be set up.
To determine if that's the case, Koel relies on the `MAIL_MAILER` value in the `.env` file.
Any non-empty value other than `log` or `array` is considered a proper mailer.
As such, if you don't need email-required features, you can simply set `MAIL_MAILER` to `log` or `array` and leave the rest of the mailer-related values empty,
and Koel will know to remove/disable these features.

## Upgrade

:::danger Backup your database
Remember to always back up your database before upgrading.
:::

Check out [Releases](https://github.com/koel/koel/releases) for upgrade guides corresponding to your Koel version and installation method.
In general, the upgrade process involves updating the source code and dependencies, running database migrations, and occasionally, adding/updating some configuration values.

### Upgrade an installation from source

If you installed Koel from source, upgrading is as simple as running the following commands:

```bash
cd <KOEL_ROOT_DIR>
git pull
git checkout latest
composer install
php artisan koel:init
```

### Upgrade a pre-compiled archive installation

For pre-compiled archived users, download the latest archive, extract it, and replace the existing files with the new ones.
Make sure, however, that old application code (typically `app` and `config` folders) is removed.

```bash
cd <KOEL_ROOT_DIR>
rm -rf app config
# assuming we're upgrading to v7.0.0
wget -qO- https://github.com/koel/koel/releases/download/v7.0.0/koel-v7.0.0.tar.gz | tar -xvzC . --strip-components=1
php artisan cache:clear
php artisan koel:init --no-assets
```

### Upgrade a Docker installation

For Docker users, the upgrade process is as simple as pulling the latest image and restarting the container.

:::tip Always read release notes
Again, no matter which installation method, always read the [release notes](https://github.com/koel/koel/releases) for specific guides
as well as other important changes you might be missing.
:::

## Downgrade

Koel does not provide a built-in downgrade mechanism.
In the unlikely event that you need to downgrade, simply restore your database from a backup and follow the installation guide for the version you want to downgrade to.
