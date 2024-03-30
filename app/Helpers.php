<?php

use App\Facades\License;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;
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

function album_cover_path(?string $fileName): ?string
{
    return $fileName ? public_path(config('koel.album_cover_dir') . $fileName) : null;
}

function album_cover_url(?string $fileName): ?string
{
    return $fileName ? static_url(config('koel.album_cover_dir') . $fileName) : null;
}

function artist_image_path(?string $fileName): ?string
{
    return $fileName ? public_path(config('koel.artist_image_dir') . $fileName) : null;
}

function artist_image_url(?string $fileName): ?string
{
    return $fileName ? static_url(config('koel.artist_image_dir') . $fileName) : null;
}

function playlist_cover_path(?string $fileName): ?string
{
    return $fileName ? public_path(config('koel.playlist_cover_dir') . $fileName) : null;
}

function playlist_cover_url(?string $fileName): ?string
{
    return $fileName ? static_url(config('koel.playlist_cover_dir') . $fileName) : null;
}

function user_avatar_path(?string $fileName): ?string
{
    return $fileName ? public_path(config('koel.user_avatar_dir') . $fileName) : null;
}

function user_avatar_url(?string $fileName): ?string
{
    return $fileName ? static_url(config('koel.user_avatar_dir') . $fileName) : null;
}

function koel_version(): string
{
    return trim(FileFacade::get(base_path('.version')));
}

/**
 * @throws Throwable
 */
function attempt(callable $callback, bool $log = true, bool $throw = false): mixed
{
    try {
        return $callback();
    } catch (Throwable $e) {
        if (app()->runningUnitTests() || $throw) {
            throw $e;
        }

        if ($log) {
            Log::error('Failed attempt', ['error' => $e]);
        }

        return null;
    }
}

function attempt_if($condition, callable $callback, bool $log = true): mixed
{
    return value($condition) ? attempt($callback, $log) : null;
}

function attempt_unless($condition, callable $callback, bool $log = true): mixed
{
    return !value($condition) ? attempt($callback, $log) : null;
}

function gravatar(string $email, int $size = 192): string
{
    return sprintf("https://www.gravatar.com/avatar/%s?s=$size&d=robohash", md5(Str::lower($email)));
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
