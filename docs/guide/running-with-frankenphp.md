---
description: Install FrankenPHP, configure Koel's Caddyfile, run artisan commands via php-cli, set up a systemd service on Ubuntu, and serve behind a reverse proxy.
---

# Running with FrankenPHP

[FrankenPHP](https://frankenphp.dev) is a modern PHP application server that bundles the webserver (Caddy) and the PHP
runtime into a single binary — replacing the typical nginx + PHP-FPM pair with one process and giving you automatic
HTTPS out of the box. Koel ships a `Caddyfile.example` at the project root that wires it up correctly.

::: info Note
FrankenPHP is only the runtime — you still need a fully prepared Koel install (i.e. `vendor/` from `composer install`
and the compiled frontend in `public/build/`) before it has anything to serve. Follow either of the installation
methods on the [Getting Started](/guide/getting-started#installation) page (pre-compiled archive or building from
source) first.
:::

## Install FrankenPHP

Pre-built binaries are published at [frankenphp.dev/docs/#install](https://frankenphp.dev/docs/#install). On Linux,
the one-line installer fetches the right binary for your architecture:

```bash
curl https://frankenphp.dev/install.sh | sh
sudo mv frankenphp /usr/local/bin/
```

## Configure the Caddyfile

From the Koel project root:

```bash
cp Caddyfile.example Caddyfile
```

Edit `Caddyfile` and replace `localhost` with the domain you'll serve from. Using a real public domain enables automatic
HTTPS via Let's Encrypt.

## Run it

```bash
frankenphp run
```

That's it — Koel is now served on port 443 (and 80, redirected to HTTPS).

## Running artisan commands

FrankenPHP bundles its own PHP, exposed via the `php-cli` subcommand. Any `php artisan …` from Koel's CLI documentation
becomes:

```bash
frankenphp php-cli artisan <command>
```

For example, `frankenphp php-cli artisan migrate` runs database migrations and `frankenphp php-cli artisan koel:sync`
triggers a media scan. For convenience: `alias artisan='frankenphp php-cli artisan'`.

If Koel was installed alongside a system PHP (used by `composer install`, etc.), running `php artisan <command>` against
the system PHP works too — both PHPs share the same `.env` and database.

::: warning DB_HOST=localhost gotcha
FrankenPHP's bundled PHP has a different compiled-in default MySQL socket path than the system PHP your distro ships.
If `.env` has `DB_HOST=localhost`, Laravel connects via Unix socket — and `frankenphp php-cli` will look at the wrong
path. Symptom: `Checking database connection … ERROR` when running `koel:init` under FrankenPHP even though
HTTP serving works fine. Fix either of:

- Override per-command: `DB_HOST=127.0.0.1 frankenphp php-cli artisan <command>`
- Or change `.env` to `DB_HOST=127.0.0.1` to force TCP for everyone (small loopback overhead, but uniform).
:::

For FrankenPHP CLI options not covered here (worker mode, multi-domain serving, custom PHP flags, etc.), refer to the
[official FrankenPHP documentation](https://frankenphp.dev/docs/).

## Run as a systemd service (Ubuntu)

For a production deployment, run FrankenPHP under `systemd` so it starts on boot and restarts on failure. Create
`/etc/systemd/system/koel.service`:

```ini
[Unit]
Description=Koel (FrankenPHP)
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/koel
ExecStart=/usr/local/bin/frankenphp run
Restart=on-failure
AmbientCapabilities=CAP_NET_BIND_SERVICE
CapabilityBoundingSet=CAP_NET_BIND_SERVICE

[Install]
WantedBy=multi-user.target
```

Adjust `WorkingDirectory` and `User` to match where Koel lives on your host. Then enable and start it:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now koel
sudo journalctl -u koel -f
```

## Behind a reverse proxy

If you're already running nginx (or another reverse proxy) and want to use FrankenPHP only as the PHP runtime, make two
changes to your `Caddyfile`:

1. **Uncomment the `auto_https off` and `servers { trusted_proxies … }` block** at the top — `Caddyfile.example` ships
   it commented and ready for this case. Adjust the trusted-proxies CIDR if the reverse proxy lives on a different
   host.
2. **Bind the site block to a loopback port** — change `localhost {` to `:8001 {` and add `bind 127.0.0.1` as the first
   line inside the block. Everything else inside the site block stays as-is.

Then point your existing reverse proxy at `127.0.0.1:8001` and let it terminate TLS as before.
