<?php

/**
 * Get a URL for static file requests.
 * If this installation of Koel has a CDN_URL configured, use it as the base.
 * Otherwise, just use a full URL to the asset.
 *
 * @param string $name The optional resource name/path
 */
function static_url(?string $name = null): string
{
    $cdnUrl = trim(config('koel.cdn.url'), '/ ');

    return $cdnUrl ? $cdnUrl . '/' . trim(ltrim($name, '/')) : trim(asset($name));
}

/**
 * A copy of Laravel Mix but catered to our directory structure.
 *
 * @throws InvalidArgumentException
 */
function asset_rev(string $file, ?string $manifestFile = null): string
{
    static $manifest = null;

    $manifestFile = $manifestFile ?: public_path('mix-manifest.json');

    if ($manifest === null) {
        $manifest = json_decode(file_get_contents($manifestFile), true);
    }

    if (isset($manifest[$file])) {
        return file_exists(public_path('hot'))
            ? "http://localhost:8080{$manifest[$file]}"
            : static_url($manifest[$file]);
    }

    throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
}

function album_cover_path(string $fileName): string
{
    return public_path(config('koel.album_cover_dir') . $fileName);
}

function album_cover_url(string $fileName): string
{
    return static_url(config('koel.album_cover_dir') . $fileName);
}

/**
 * @see album_cover_url()
 */
function album_thumbnail_url(string $fileName): string
{
    return album_cover_url($fileName);
}

function artist_image_path(string $fileName): string
{
    return public_path(config('koel.artist_image_dir') . $fileName);
}

function artist_image_url(string $fileName): string
{
    return static_url(config('koel.artist_image_dir') . $fileName);
}

function koel_version(): string
{
    return trim(file_get_contents(base_path('.version')));
}
