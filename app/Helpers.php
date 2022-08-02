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

function koel_version(): string
{
    return trim(file_get_contents(base_path('.version')));
}
