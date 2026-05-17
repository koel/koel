---
description: Install Koel from the single-archive standalone bundle (FrankenPHP + Caddy + PHP + Koel app), customize the database, and run as a systemd service.
---

# Standalone Binary

The [koel/franken](https://github.com/koel/franken/releases) distribution bundles
[FrankenPHP](https://frankenphp.dev) (Caddy + the PHP runtime) with the compiled Koel application code into a
single archive — no Composer, Node, or system PHP needed on the host. Uses SQLite by default for zero-setup
operation. The easiest way to get Koel running.

## Install and run

Download the archive matching your platform from the
[releases page](https://github.com/koel/franken/releases) — available for `linux-x86_64`, `linux-aarch64`,
`mac-x86_64`, and `mac-arm64`. Extract it and run:

```bash
tar -xzf koel-franken-v9.3.2-linux-x86_64.tar.gz
cd koel-franken-v9.3.2-linux-x86_64
./koel php-server --listen :8000           # plain HTTP on :8000
# or, with a real domain (auto-HTTPS via Let's Encrypt):
./koel php-server --domain koel.example.com
```

::: warning On macOS
The bundled FrankenPHP binary isn't code-signed, so Gatekeeper blocks it on first run with a
"frankenphp can't be verified" dialog. Strip the quarantine flag from the extracted bundle once and
the binary will run on subsequent invocations:

```bash
xattr -dr com.apple.quarantine .
```

Run this from inside the extract directory before `./koel php-server …`.
:::

On first run, the launcher provisions `$HOME/.koel/` with the conventional layout, generates an `APP_KEY`,
and runs database migrations against a fresh SQLite database. Then FrankenPHP serves Koel on the chosen
port or domain.

## What lives where

Everything writable lives under `$HOME/.koel/`:

| Path | What |
|---|---|
| `$HOME/.koel/.env` | Environment file (Koel's config) |
| `$HOME/.koel/db.sqlite` | SQLite database |
| `$HOME/.koel/storage/` | Laravel storage path (logs, sessions, cache, uploaded images) |
| `$HOME/.koel/storage/app/artifacts/` | Transcodes, downloaded podcasts, temp downloads |
| `$HOME/.koel/php.d/koel.ini` | PHP-INI overrides (display_errors off, error_reporting mask, 512M uploads) |

The bundle itself is conceptually immutable — anything you'd want to back up or migrate lives in
`$HOME/.koel/`.

## Running Artisan commands

The bundle ships an `./artisan` shortcut next to `./koel`:

```bash
./artisan koel:sync
./artisan tinker
```

It's a thin wrapper that re-execs the launcher (`./koel php-cli artisan …`), so all the bundle's PHP runtime
and env wiring stays in scope. See
[Running with FrankenPHP](/guide/running-with-frankenphp#running-artisan-commands) for the full set of
caveats (notably the `DB_HOST=localhost` MySQL-socket gotcha).

## Customization and migration

The launcher only auto-writes the SQLite-flavored `.env` on **first run, when `$HOME/.koel/.env` does not
yet exist**. If you want a different database or other custom configuration, you have two paths:

- **Bring your own `.env`**: create `$HOME/.koel/.env` (with `DB_CONNECTION=mysql` or `pgsql` + the
  corresponding credentials, plus any other settings) *before* the first `./koel` invocation. The launcher
  will see the file already exists, skip the SQLite seeding, and run migrations against your DB.
- **Override after the fact**: let the bundle auto-init, then edit `$HOME/.koel/.env` to point at your DB
  and re-run `./artisan migrate --force` against the new connection.

Migrating an existing Koel install into the bundle isn't a turnkey path: beyond `.env`, you'd need to copy
your media files (or repoint `MEDIA_PATH`), uploaded images (`storage/app/public/images/`), search indexes,
and so on into `$HOME/.koel/storage/`. For an existing production install,
[Pre-Compiled Archive](/guide/getting-started#using-a-pre-compiled-archive) or
[Building from Source](/guide/getting-started#building-from-source) is usually less work.

## Running as a systemd service (Ubuntu)

For a production deployment, run the bundle under `systemd` so it starts on boot and restarts on failure.
Place the extracted bundle somewhere stable (e.g. `/opt/koel`) and create
`/etc/systemd/system/koel.service`:

```ini
[Unit]
Description=Koel (standalone bundle)
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

Adjust `WorkingDirectory`, `User`, and the `--domain` (or `--listen`) flag to match your host. The
`AmbientCapabilities` line lets the non-root service bind to ports 80/443 for automatic HTTPS. The
launcher will provision `~koel/.koel/` (the service user's `$HOME/.koel/`) on first start.

Then enable and start it:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now koel
sudo journalctl -u koel -f
```

## Behind a reverse proxy

If you're already running nginx (or another reverse proxy) in front of Koel, the bundle's launcher accepts
the same FrankenPHP arguments as the standalone setup. Bind to a loopback port:

```bash
./koel php-server --listen 127.0.0.1:8001
```

Then point your existing reverse proxy at `127.0.0.1:8001` and let it terminate TLS as before.
