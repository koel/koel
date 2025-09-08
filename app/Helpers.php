<?php

use App\Facades\License;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Get a URL for static file requests.
 * If this installation of Koel has a CDN_URL configured, use it as the base.
 * Otherwise, just use a full URL to the asset.
 *
 * @param string|null $name The optional resource name/path
 */
function static_url(?string $name = null): string
{
    $cdnUrl = trim(config('koel.cdn.url'), '/ ');

    return $cdnUrl ? $cdnUrl . '/' . trim(ltrim($name, '/')) : trim(asset($name));
}

function base_url(): string
{
    return app()->runningUnitTests() ? config('app.url') : asset('');
}

function image_storage_path(?string $fileName): ?string
{
    return $fileName ? public_path(config('koel.image_storage_dir') . $fileName) : null;
}

function image_storage_url(?string $fileName): ?string
{
    return $fileName ? static_url(config('koel.image_storage_dir') . $fileName) : null;
}

function artifact_path(?string $subPath = null, $ensureDirectoryExists = true): string
{
    $path = Str::finish(config('koel.artifacts_path'), DIRECTORY_SEPARATOR);

    if ($subPath) {
        $path .= ltrim($subPath, DIRECTORY_SEPARATOR);
    }

    if ($ensureDirectoryExists) {
        File::ensureDirectoryExists(Str::endsWith($path, DIRECTORY_SEPARATOR) ? $path : dirname($path));
    }

    return $path;
}

function koel_version(): string
{
    return trim(File::get(base_path('.version')));
}

function rescue_if($condition, callable $callback): mixed
{
    return value($condition) ? rescue($callback) : null;
}

function rescue_unless($condition, callable $callback): mixed
{
    return !value($condition) ? rescue($callback) : null;
}

function gravatar(string $email, int $size = 192): string
{
    $url = config('services.gravatar.url');
    $default = config('services.gravatar.default');

    return sprintf("%s/%s?s=$size&d=$default", $url, md5(Str::lower($email)));
}

function avatar_or_gravatar(?string $avatar, string $email): string
{
    if (!$avatar) {
        return gravatar($email);
    }

    if (Str::startsWith($avatar, ['http://', 'https://'])) {
        return $avatar;
    }

    return image_storage_url($avatar);
}

/**
 * A quick check to determine if a mailer is configured.
 * This is not bulletproof but should work in most cases.
 */
function mailer_configured(): bool
{
    return config('mail.default') && !in_array(config('mail.default'), ['log', 'array'], true);
}

/** @return array<string> */
function collect_sso_providers(): array
{
    if (License::isCommunity()) {
        return [];
    }

    $providers = [];

    if (
        config('services.google.client_id')
        && config('services.google.client_secret')
        && config('services.google.hd')
    ) {
        $providers[] = 'Google';
    }

    return $providers;
}

function get_mtime(string|SplFileInfo $file): int
{
    $file = is_string($file) ? new SplFileInfo($file) : $file;

    // Workaround for #344, where getMTime() fails for certain files with Unicode names on Windows.
    return rescue(static fn () => $file->getMTime()) ?? time();
}

/**
 * Simple, non-cryptographically secure hash function for strings.
 * This is used for generating hashes for identifiers that do not require high security.
 */
function simple_hash(?string $string): string
{
    return md5("koel-hash:$string");
}

function is_image(string $path): bool
{
    return rescue(static fn () => (bool) exif_imagetype($path)) ?? false;
}

/**
 * @param string|int ...$parts
 */
function cache_key(...$parts): string
{
    return simple_hash(implode('.', $parts));
}

/**
 * @return array<string>
 */
function collect_accepted_audio_extensions(): array
{
    return array_values(
        collect(array_values(config('koel.streaming.supported_mime_types')))
            ->flatten()
            ->unique()
            ->map(static fn (string $ext) => Str::lower($ext))
            ->toArray()
    );
}

function find_ffmpeg_path(): ?string
{
    // for Unix-like systems, we can use the `which` command
    if (PHP_OS_FAMILY !== 'Windows') {
        $path = trim(shell_exec('which ffmpeg') ?: '');

        return $path && is_executable($path) ? $path : null;
    }

    // for Windows, we can check `where` command
    $path = trim(shell_exec('where ffmpeg') ?: '');

    if ($path && is_executable($path)) {
        return $path;
    }

    // finally, check the PATH environment variable
    $path = getenv('PATH');

    if ($path) {
        $paths = explode(PATH_SEPARATOR, $path);

        foreach ($paths as $dir) {
            $ffmpegPath = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . 'ffmpeg.exe';

            if (is_executable($ffmpegPath)) {
                return $ffmpegPath;
            }
        }
    }

    return null;
}
