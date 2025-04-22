---
outline: [2, 2]
---

# Troubleshooting

While Koel strives to be as user-friendly and bug-free as possible, things can still go wrong.
Don't panic! This page will guide you through the process of troubleshooting your issues.

## First Steps

When a wild error appears, the very first step you should take is to check `storage/logs/laravel.log`.
More often than not, this file will provide you with a lot of details and hints on what went wrong.
This is so important that it is worth repeating:

:::danger Always check the log
If you receive an error, the first step is to take a look at `storage/logs/laravel.log`.
:::

Next, look at the browser console for any JavaScript errors.
While you're at it, check the network tab for any failed requests and try disabling the network cache.

Also, try clearing the cache, reinstalling dependencies, and recompiling the front-end assets.
Below are a couple of commands that might help in this area:

```bash
# Remove Composer vendor/ directory and reinstall the packages
rm -rf vendor && composer install

# Clear node_modules, re-install, and re-build the front-end assets
rm -rf node_modules && yarn install && yarn build

# Clear the Laravel cache. This command is automatically run after `composer install`.
php artisan cache:clear

# Clear the Laravel config cache
php artisan config:clear
```

### Run Koel Doctor

For Linux and macOS systems, Koel comes with a `doctor` command that checks your setup for common issues.
You can run it by executing the following **as your web server user**:

```bash
php artisan koel:doctor
```

This command will check your environment and configuration for common issues (file/folder permissions, storage setup, server configuration, etc.) and provide you with a report.
An example output might look like this:

![Koel Doctor](./assets/img/doctor.webp)

If you're still stuck, check below for a couple of common issues and their solutions.

## Common Issues

::: details You run into a permission issue
Make sure your web server has the necessary permissions to _recursively_ read/write to critical folders like `storage`, `bootstrap/cache`, and `public`.
Also, remember to run artisan commands as your web server user (e.g. `www-data` or `nginx`), **never** as `root`, as these commands might create files that your web server user must have access to.
If you use the Docker installation, for example, run the scan command as the `www-data` user as follows:

```bash
docker exec --user www-data <container_name_for_koel> php artisan koel:scan
```
:::

::: details You receive a `Class 'Pusher' not found` error
Add or set `BROADCAST_DRIVER=log` in your `.env` file. This will instruct Laravel to use `log` as the default broadcast driver instead.
:::

::: details You receive an "Unknown error" when scanning using the web interface
Try scanning from the command line with `php artisan koel:sync`. Most of the time, you should receive a more detailed, easier to debug, message.
See also: [Music Discovery](usage/music-discovery).
:::

::: details You receive an `Integrity constraint violation: 1062 Duplicate entry for key 'artists_name_unique'` error when scanning
Set your database and table collation to `utf8_unicode_ci` or `utf8mb4_unicode_ci`.
:::

::: details You receive an &lt;input random strings here&gt; error when running `yarn`
This most likely has little to do with Koel but more with your node/npm/yarn environment and installation. Deleting `node_modules` and rerunning the command sometimes help.
:::

::: details Song stops playing, and you receive a `Failed to load resource: net::ERR_CONTENT_LENGTH_MISMATCH` error
This may sometimes happen with the native PHP streaming method. Check [Streaming Music](usage/streaming) for alternatives.
:::

::: details You receive a `Multiple licenses found` warning when running `koel:license:status` command
Koel Plus only requires one license key. If it detects more than one key in the database, the warning will be issued.
Most of the time this shouldn't cause any problem, but if you're experiencing issues, try emptying the `licenses` table and re-activating your license key.
:::

## Some Errors I have encountered

---

**Symfony Exception**
`Vite manifest not found at: /srv/koel/www/public/build/manifest.json (View: /srv/koel/www/resources/views/index.blade.php)`

**Explanation**
Vite needs to build its assets. This can be done by either executing 
`npm run build` 
or 
`node /srv/koel/www/node_modules/vite/bin/vite.js build` 

**Solution:**

```bash
sudo su -l koel
cd /srv/koel/www
npm install
npm audit fix
npm install # yes again
npm run build
```

---

***File does not exist at path /srv/koel/www/.version***
copy it from github

---

***Database connection does not work? Using postgres?***

here you will be greeted with the koel installation wizard over and over again

   ```bash
   ************************************
   *     KOEL INSTALLATION WIZARD     *
   ************************************

   As a reminder, you can always install/upgrade manually following the guide at https://docs.koel.dev

   Clearing caches ............................... 5ms DONE
   .env file exists -- skipping .................. DONE
   Retrieving app key ............................ 0ms DONE
   Using app key: base64:uIq4Ft3m7.. ............. DONE

   WARN  Cannot connect to the database. Let's set it up.

   Your DB driver of choice [MySQL/MariaDB]:
    [mysql     ] MySQL/MariaDB
    [pgsql     ] PostgreSQL
    [sqlsrv    ] SQL Server
    [sqlite-e2e] SQLite
   > pgsql

   DB host:
   > localhost

   DB port (leave empty for default):
   > 5432

   DB name:
   > koel

   DB user:
   > koel

   DB password:
   > *************************
   ```


Check if you installed 

php-pgsql

i.e. php-pgsql-0:8.3.20-1.fc42.remi.x86_64 on fedora 

## Reinstalling Koel

In the worst case scenario, you can always reinstall Koel. Although Koel doesn't provide a built-in way to reinstall itself, you can do so manually by following these steps:

1. Backup your database
2. Have you backed up your database yet?
3. No seriously, make sure you have a backup of your database
4. Back up the `public/img` directory. This is where your album art, artist images, user avatars etc. are stored.
5. Delete or empty the root Koel directory
6. Follow the [installation guide](guide/getting-started#installation) to install Koel afresh
7. Restore your database and the `public/img` directory

By now you should have a fresh Koel installation with all your data intact and hopefully without the issue you were facing.

## Ask for Help

If you're still stuck, the [issue page](https://github.com/koel/koel/issues) on GitHub is a good place to ask for help. Remember to be civil and patient.
