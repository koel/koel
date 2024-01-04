<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\File;

trait SandboxesTests
{
    private static function createSandbox(): void
    {
        config(['koel.album_cover_dir' => 'sandbox/img/covers/']);
        config(['koel.artist_image_dir' => 'sandbox/img/artists/']);

        File::ensureDirectoryExists(public_path(config('koel.album_cover_dir')));
        File::ensureDirectoryExists(public_path(config('koel.artist_image_dir')));
        File::ensureDirectoryExists(public_path('sandbox/media/'));
    }

    private static function destroySandbox(): void
    {
        File::deleteDirectory(public_path('sandbox'));
    }
}
