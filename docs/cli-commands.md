---
outline: [2, 3]
---

# CLI Commands

Koel comes with a set of handy CLI commands to help you manage your installation.
These commands are available via Laravel’s `artisan` command line interface.

You can run `php artisan list` from your Koel installation directory and pipe the output to `grep` to filter out those under the `koel` namespace:

```bash
php artisan list | grep koel
 koel
  koel:admin:change-password  Change a user's password
  koel:init                   Install or upgrade Koel
  koel:license:activate       Activate a Koel Plus license
  koel:license:deactivate     Deactivate the currently active Koel Plus license
  koel:license:status         Check the current Koel Plus license status
  koel:podcasts:sync          Synchronize podcasts.
  koel:prune                  Remove empty artists and albums
  ...
```

In order to get help on a specific command, run `php artisan <command> -h`.

## Available Commands

:::warning Warning
Always run commands as your web server user (e.g. `www-data` or `nginx`), **never** as `root`.
Otherwise, Koel might encounter issues with file permissions (e.g. with cache and storage files) and you might end up with a broken installation.

With the Docker installation, for example, run the command as the `www-data` user:

```bash
docker exec --user www-data <container_name_for_koel> php artisan <command>
```
:::

### `koel:admin:change-password`

Change a user's password.

#### Usage

```bash
php artisan koel:admin:change-password [<email>]
```

#### Arguments
| Name    | Description                                                  |
|---------|--------------------------------------------------------------|
| `email` | The user's email. If empty, will get the default admin user. |

### `koel:init`

Install or upgrade Koel.

Usage

```bash
php artisan koel:init [options]
```

#### Options
| Name          | Description                     |
|---------------|---------------------------------|
| `--no-assets` | Do not compile front-end assets |

### `koel:license:activate`

Activate a Koel Plus license.

#### Usage

```bash
php artisan koel:license:activate <key>
```

#### Arguments

| Name  | Description                  |
|-------|------------------------------|
| `key` | The license key to activate. |

### `koel:license:deactivate`

Deactivate the currently active Koel Plus license.

#### Usage

```bash
php artisan koel:license:deactivate
```

### `koel:license:status`

Check the current Koel Plus license status.

#### Usage

```bash
php artisan koel:license:status
```

### `koel:podcasts:sync`

Synchronize podcasts.

#### Usage

```bash
php artisan koel:podcasts:sync
```

### `koel:prune`

Remove empty artists and albums.

#### Usage

```bash
php artisan koel:prune
```

### `koel:scan`

Scan for songs in the configured directory.

#### Usage

```bash
php artisan koel:scan [options]
php artisan koel:sync [options] # Alias, deprecated
```

#### Options:

| Name             | Description                                                                                                                                                                   |
|------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `O`, `--owner=`  | The ID of the user who should own the newly scanned songs. Defaults to the first admin user.                                                                                  |
| `P`, `--private` | Whether to make the newly scanned songs private to the user.                                                                                                                  |
| `I`, `--ignore=` | The comma-separated tags to ignore (exclude) from scanning. Valid tags are `title`, `album`,`artist`, `albumartist`, `track`, `disc`, `year`, `genre`, `lyrics`, and `cover`. |
| `F`, `--force`   | Force re-scanning even unchanged files.                                                                                                                                       |

### `koel:search:import`

Import all searchable entities with Scout. See [Instant Search](./usage/search) for more information.

#### Usage

```bash
php artisan koel:search:import
```

### `koel:storage`

Set up and configure Koel’s storage.

#### Usage

```bash
php artisan koel:storage
```

### `koel:storage:dropbox` <PlusBadge />

Set up Dropbox as the storage driver for Koel.

#### Usage

```bash
php artisan koel:storage:dropbox
```

### `koel:storage:local`

Set up the local storage for Koel. A "local storage" is simply a directory on the server where Koel is installed.

#### Usage

```bash
php artisan koel:storage:local
```

### `koel:storage:s3` <PlusBadge />

Set up Amazon S3 or a compatible service (DigitalOcean Spaces, Cloudflare R2, etc.) as the storage driver for Koel.

#### Usage

```bash
php artisan koel:storage:s3
```

:::tip
To set up the storage driver for Koel, simply use `koel:storage`. Internally, it calls `koel:storage:local`, `koel:storage:s3`, or `koel:storage:dropbox` based on your input.
:::

### `koel:tags:collect`

Collect additional tags from existing songs. This is a legacy command and is no longer needed for new installations.

#### Usage

```bash
php artisan koel:tags:collect
```

## Command Scheduling

Some of the commands, such as `koel:scan` and `koel:prune`, can be scheduled to run at regular intervals.
Koel uses Laravel’s built-in scheduler to manage this.

In order to set up the scheduler, you need to add the following cron entry into the crontab of the webserver user (for example,
if it's `www-data`, run `sudo crontab -u www-data -e`):

```bash
* * * * * cd /path-to-koel-installation && php artisan schedule:run >> /dev/null 2>&1
```

This will run the scheduler every minute, which will then run any scheduled commands as needed.
By default, `koel:scan`, `koel:prune`, and `koel:podcasts:sync` are set to run every day at midnight.

Though you can still manually set up cron jobs for individual commands, the scheduler is the recommended approach to command scheduling in Koel, as it will automatically cover any commands that may be added in the future.
