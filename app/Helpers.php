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
