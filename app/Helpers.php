<?php

use App\Facades\License;
use Illuminate\Support\Facades\File as FileFacade;
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
    return sprintf("https://www.gravatar.com/avatar/%s?s=$size&d=robohash", md5(Str::lower($email)));
}

function avatar_or_gravatar(?string $avatar, string $email): string
{
    if (!$avatar) {
        return gravatar($email);
    }

    if (Str::startsWith($avatar, ['http://', 'https://'])) {
        return $avatar;
    }

    return user_avatar_url($avatar);
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
