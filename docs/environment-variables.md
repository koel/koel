---
description: Comprehensive reference of all Koel .env variables for storage, scanning, streaming, integrations, SSO, and more.
---

# Environment Variables

This page documents the environment variables specific to Koel. These variables are typically set in the `.env` file
at the root of your Koel installation.

:::info Laravel Variables
Koel is built on Laravel, which has its own set of environment variables (e.g. `APP_ENV`, `APP_DEBUG`, `APP_KEY`,
`APP_URL`, `DB_*`, `CACHE_DRIVER`, `QUEUE_CONNECTION`, `SESSION_DRIVER`, etc.).
These are not documented here — please refer to the [Laravel documentation](https://laravel.com/docs/configuration)
for details.
:::

## Storage

| Variable | Description | Default |
|---|---|---|
| `STORAGE_DRIVER` | The storage driver for your media files. Valid values: `local`, `sftp`, `s3` (Koel Plus), `dropbox` (Koel Plus). See [Cloud Storage Support](plus/cloud-storage-support). | `local` |
| `MEDIA_PATH` | The absolute path to your media directory. Required when using `STORAGE_DRIVER=local`. Can also be changed via the web interface. | _(empty)_ |
| `ARTIFACTS_PATH` | The absolute path to store Koel artifacts (transcoded files, podcast episodes, temporary downloads, etc.). If empty, uses the system's temporary directory. | _(empty)_ |
| `LARAVEL_STORAGE_PATH` | Absolute path Koel uses for writable runtime data — album/artist images (under `app/public/images`), logs, search indexes, framework cache, sessions. Set this to keep mutable state outside the application directory (read-only deploys, multi-instance setups, etc.). After setting it, run `php artisan storage:link` (or `composer koel:init`) so `public/storage` symlinks to `LARAVEL_STORAGE_PATH/app/public`. | `<app>/storage` |

:::warning Upgrading an older Koel install
The image storage location moved from `public/img/storage/` to `storage/app/public/images/`
(or `LARAVEL_STORAGE_PATH/app/public/images/` when set). If you have a `public/img/storage/`
directory with files in it, migrate them once or album/artist art will appear broken:

```bash
mkdir -p "${LARAVEL_STORAGE_PATH:-$(pwd)/storage}/app/public/images"
rsync -a public/img/storage/ "${LARAVEL_STORAGE_PATH:-$(pwd)/storage}/app/public/images/"
php artisan storage:link
```

After confirming images render, the old `public/img/storage/` directory can be removed.
:::

### S3 / S3-Compatible

Required when `STORAGE_DRIVER=s3`. Remember to set your CORS policy to allow access from Koel's domain.

| Variable | Description | Default |
|---|---|---|
| `AWS_ACCESS_KEY_ID` | Your AWS (or S3-compatible service) access key ID. | _(empty)_ |
| `AWS_SECRET_ACCESS_KEY` | Your AWS secret access key. | _(empty)_ |
| `AWS_REGION` | The region of your bucket. Set to `auto` for Cloudflare R2. | _(empty)_ |
| `AWS_ENDPOINT` | The endpoint URL for S3-compatible services. | _(empty)_ |
| `AWS_BUCKET` | The name of your S3 bucket. | _(empty)_ |

### Dropbox

Required when `STORAGE_DRIVER=dropbox`. Run `php artisan koel:setup-dropbox` to set all Dropbox-related variables.

| Variable | Description | Default |
|---|---|---|
| `DROPBOX_APP_KEY` | Your Dropbox app key. | _(empty)_ |
| `DROPBOX_APP_SECRET` | Your Dropbox app secret. | _(empty)_ |
| `DROPBOX_REFRESH_TOKEN` | Your Dropbox refresh token. | _(empty)_ |

### SFTP

Required when `STORAGE_DRIVER=sftp`.

| Variable | Description | Default |
|---|---|---|
| `SFTP_HOST` | The SFTP server hostname. | _(empty)_ |
| `SFTP_PORT` | The SFTP server port. | _(empty)_ |
| `SFTP_ROOT` | The absolute path on the SFTP server to store media files. | _(empty)_ |
| `SFTP_USERNAME` | The SFTP username. | _(empty)_ |
| `SFTP_PASSWORD` | The SFTP password. | _(empty)_ |
| `SFTP_PRIVATE_KEY` | Path to the private key for key-based authentication (alternative to password). | _(empty)_ |
| `SFTP_PASSPHRASE` | The passphrase for the private key. | _(empty)_ |

## Media Scanning

| Variable | Description | Default |
|---|---|---|
| `APP_MAX_SCAN_TIME` | The maximum scan time in seconds when scanning via the browser. Does not affect `koel:sync`. | `600` |
| `MEMORY_LIMIT` | The memory limit in MB for the scanning process. Example: `2048`. | _(empty)_ |
| `SCAN_JOBS` | The number of parallel worker processes for scanning. Set to `1` to disable parallel scanning. Can be overridden with `--jobs` flag. | `4` |
| `IGNORE_DOT_FILES` | Whether to ignore dot files and folders when scanning. Greatly improves performance if your media root has folders like `.git` or `.cache`. | `true` |
| `SYNC_LOG_LEVEL` | The verbosity of sync logs (found under `storage/logs/`). Options: `all`, `error`. | `error` |

## Streaming & Transcoding

| Variable | Description | Default |
|---|---|---|
| `STREAMING_METHOD` | The streaming method. Options: `php`, `x-sendfile`, `x-accel-redirect`. See [Streaming Music](usage/streaming). Using `x-sendfile` or `x-accel-redirect` is highly recommended for better performance. | `php` |
| `TRANSCODE_FLAC` | Whether to transcode FLAC to MP3 on the fly. Set to `false` to stream FLAC as-is. | `true` |
| `TRANSCODE_BIT_RATE` | The bit rate (in kbps) for transcoded audio. Higher values mean better quality but slower streaming. | `128` |
| `FFMPEG_PATH` | The full path to the ffmpeg binary. Automatically detected if left empty. | _(auto-detected)_ |
| `TRANSCODE_TIMEOUT` | The maximum time in seconds allowed for transcoding a single file. Increase for very large files. `0` disables the timeout. | `300` |

## Downloading

| Variable | Description | Default |
|---|---|---|
| `ALLOW_DOWNLOAD` | Whether to allow song downloading. Multi-song downloads require the `zip` PHP extension. | `true` |
| `DOWNLOAD_LIMIT` | The maximum number of songs allowed in a single download. `0` means unlimited. Single-song downloads are always allowed. | `0` |

## Service Integrations

Also see [Service Integrations](service-integrations) for detailed setup instructions.

| Variable | Description | Default |
|---|---|---|
| `USE_MUSICBRAINZ` | Whether to use MusicBrainz for metadata fetching. | `true` |
| `MUSICBRAINZ_USER_AGENT` | The user agent for MusicBrainz API requests. Auto-generated if empty. | _(auto-generated)_ |
| `LASTFM_API_KEY` | Your Last.fm API key. Required for artist/album info and scrobbling. | _(empty)_ |
| `LASTFM_API_SECRET` | Your Last.fm API secret. | _(empty)_ |
| `SPOTIFY_CLIENT_ID` | Your Spotify application client ID. Used for fetching artist and album images. | _(empty)_ |
| `SPOTIFY_CLIENT_SECRET` | Your Spotify application client secret. | _(empty)_ |
| `YOUTUBE_API_KEY` | Your YouTube API key. See [YouTube integration](service-integrations#youtube). | _(empty)_ |
| `TICKETMASTER_API_KEY` | Your Ticketmaster API key. See [Ticketmaster](plus/ticketmaster). | _(empty)_ |
| `TICKETMASTER_DEFAULT_COUNTRY_CODE` | Fallback country code for Ticketmaster when IP-based lookup fails. See [ISO 3166-1 alpha-2](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2). | `US` |
| `IPINFO_TOKEN` | Your IPinfo token, used to look up the user's country for Ticketmaster. | _(empty)_ |

## SSO (Single Sign-On)

Koel Plus only. See [Single Sign-On](plus/sso).

| Variable | Description | Default |
|---|---|---|
| `SSO_GOOGLE_CLIENT_ID` | Your Google OAuth client ID. | _(empty)_ |
| `SSO_GOOGLE_CLIENT_SECRET` | Your Google OAuth client secret. | _(empty)_ |
| `SSO_GOOGLE_HOSTED_DOMAIN` | The Google Workspace domain users must belong to. | _(empty)_ |

## Proxy Authentication

Koel Plus only. See [Proxy Authentication](plus/proxy-auth).

| Variable | Description | Default |
|---|---|---|
| `PROXY_AUTH_ENABLED` | Whether to enable proxy authentication. | `false` |
| `PROXY_AUTH_USER_HEADER` | The header containing the unique user identifier. | `remote-user` |
| `PROXY_AUTH_PREFERRED_NAME_HEADER` | The header containing the user's preferred display name. | `remote-preferred-name` |
| `PROXY_AUTH_ALLOW_LIST` | A comma-separated list of allowed proxy IPs or CIDRs. If empty, no requests are allowed. | _(empty)_ |

## AI Assistant

| Variable | Description | Default |
|---|---|---|
| `AI_ENABLED` | Enable the AI assistant feature. Requires a configured AI provider. | `false` |
| `AI_PROVIDER` | The AI provider to use. Supported values: `openai`, `anthropic`, `gemini`, `ollama`, and more. See `config/ai.php` for the full list. | `openai` |
| `OPENAI_API_KEY` | API key for OpenAI. Required when `AI_PROVIDER=openai`. | _(empty)_ |
| `ANTHROPIC_API_KEY` | API key for Anthropic (Claude). Required when `AI_PROVIDER=anthropic`. | _(empty)_ |

Additional providers (Gemini, Ollama, etc.) can be configured in `config/ai.php`.

## Miscellaneous

| Variable | Description | Default |
|---|---|---|
| `TRUSTED_HOSTS` | A comma-separated list of hostnames allowed to access Koel. Leave empty to allow any hostname. Example: `localhost,192.168.0.1,yourdomain.com` | _(empty)_ |
| `FORCE_HTTPS` | Force Koel to use HTTPS URLs. Set to `true` if automatic detection fails. | `false` |
| `BACKUP_ON_DELETE` | Whether to create a backup of a song when deleting it from the filesystem. | `true` |
| `CDN_URL` | A CDN URL mapped to Koel's home URL, used to serve media files. No trailing slash. | _(empty)_ |
| `MEDIA_BROWSER_ENABLED` | Whether to enable the media browser (experimental Koel Plus feature). | `false` |
