<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\File;

trait SandboxesTests
{
    private static function createSandbox(): void
    {
        config(['koel.album_cover_dir' => 'public/sandbox/img/covers/']);
        config(['koel.artist_image_dir' => 'public/sandbox/img/artists/']);

        @mkdir(base_path(config('koel.album_cover_dir')), 0755, true);
        @mkdir(base_path(config('koel.artist_image_dir')), 0755, true);
    }

    private static function destroySandbox(): void
    {
        File::deleteDirectory(base_path('public/sandbox'));
    }
}
