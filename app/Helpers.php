<?php

namespace App\Helpers;

function album_cover_path(string $fileName): string {
    return public_path(config('koel.album_cover_dir') . $fileName);
}

function album_cover_url(string $fileName): string {
    return app()->staticUrl(config('koel.album_cover_dir') . $fileName);
}

/**
 * @see album_cover_url()
 */
function album_thumbnail_url(string $fileName): string {
    return album_cover_url($fileName);
}

function artist_image_path(string $fileName): string {
    return public_path(config('koel.artist_image_dir') . $fileName);
}

function artist_image_url(string $fileName): string {
    return app()->staticUrl(config('koel.artist_image_dir') . $fileName);
}

