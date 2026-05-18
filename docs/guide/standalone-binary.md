---
description: Install Koel from the single-archive standalone distribution (FrankenPHP + Caddy + PHP + Koel app), customize the database, and run as a systemd service.
---

# Standalone Binary

The [koel/franken](https://github.com/koel/franken/releases) distribution packages
[FrankenPHP](https://frankenphp.dev) (Caddy + the PHP runtime) with the compiled Koel application into a
single archive — no Composer, Node, or system PHP needed on the host. Uses SQLite by default for zero-setup
operation. The easiest way to get Koel running.

## Install and run

Download the archive matching your platform from the
[releases page](https://github.com/koel/franken/releases) — available for `linux-x86_64`, `linux-aarch64`,
`mac-x86_64`, and `mac-arm64`. Extract it and run:

```bash
tar -xzf koel-franken-v9.3.2-linux-x86_64.tar.gz
cd koel-franken-v9.3.2-linux-x86_64
# plain HTTP on :8000
./koel php-server --listen :8000
# or, with a real domain (auto-HTTPS via Let's Encrypt):
./koel php-server --domain koel.example.com
```

::: warning On macOS
The included FrankenPHP binary isn't code-signed, so Gatekeeper blocks it on first run with a
"frankenphp can't be verified" dialog. Strip the quarantine flag from the extracted directory once and
the binary will run from then on:

```bash
xattr -dr com.apple.quarantine .
```

Run this from inside the extract directory before `./koel php-server …`.
:::

On first run, Koel sets up `$HOME/.koel/`, generates an app key, and creates a fresh SQLite database.
FrankenPHP then starts serving on the chosen port or domain. You can now
[set up the storage](../cli-commands.md#koel-storage) and start using Koel.

## What lives where

Everything writable lives under `$HOME/.koel/`:

| Path                                 | What                                                                       |
|--------------------------------------|----------------------------------------------------------------------------|
| `$HOME/.koel/.env`                   | Environment file (Koel's config)                                           |
| `$HOME/.koel/db.sqlite`              | SQLite database                                                            |
| `$HOME/.koel/storage/`               | Laravel storage path (logs, sessions, cache, uploaded images)              |
| `$HOME/.koel/storage/app/artifacts/` | Transcodes, downloaded podcasts, temp downloads                            |
| `$HOME/.koel/php.d/koel.ini`         | PHP-INI overrides (display_errors off, error_reporting mask, 512M uploads) |

The installation directory itself doesn't change after extraction — anything worth backing up or moving
lives in `$HOME/.koel/`.

## Running Artisan commands

The distribution ships an `./artisan` shortcut next to `./koel`:

```bash
./artisan koel:sync
./artisan tinker
```

It's a shortcut for `./koel php-cli artisan …`, using the same PHP runtime and environment. See
[Running with FrankenPHP](/guide/running-with-frankenphp#running-artisan-commands) for the full set of
caveats (notably the `DB_HOST=localhost` MySQL-socket gotcha).

## Customization and migration

To use a different database or change any other setting, edit `$HOME/.koel/.env`. If you changed the
database connection, run `./artisan migrate --force` afterwards.

To move an existing Koel install into the standalone setup:

1. Copy your `.env` to `$HOME/.koel/.env`.
2. Copy your media files into place, or set `MEDIA_PATH` in `.env` to point at where they already live.
3. Copy `storage/app/public/images/`, your search indexes, and any other data from the old install into
   `$HOME/.koel/storage/`.

## Update

To upgrade to a newer release:

1. Download the new archive from the [releases page](https://github.com/koel/franken/releases).
2. Extract it over the existing directory.
3. Restart the server.

Your data in `$HOME/.koel/` — settings, database, uploaded images, search indexes — is preserved across upgrades.

::: tip New environment variables
New Koel versions occasionally introduce new settings. After upgrading, compare your `~/.koel/.env`
against `app/.env.example` to spot any new keys you might want to set.
:::

## Running as a systemd service (Ubuntu)

For a production deployment, run Koel under `systemd` so it starts on boot and restarts on failure.
Place the extracted directory somewhere stable (e.g. `/opt/koel`) and create
`/etc/systemd/system/koel.service`:

```ini
[Unit]
Description=Koel (standalone)
After=network.target

[Service]
Type=simple
User=koel
WorkingDirectory=/opt/koel
ExecStart=/opt/koel/koel php-server --domain koel.example.com
Restart=on-failure
AmbientCapabilities=CAP_NET_BIND_SERVICE
CapabilityBoundingSet=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
```

Adjust `WorkingDirectory`, `User`, and the `--domain` (or `--listen`) flag to match your host.

Then enable and start it:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now koel
sudo journalctl -u koel -f
```

## Behind a reverse proxy

If you're already running nginx (or another reverse proxy) in front of Koel, bind Koel to a local port:

```bash
./koel php-server --listen 127.0.0.1:8001
```

Then point your existing reverse proxy at `127.0.0.1:8001` and let it terminate TLS as before.
