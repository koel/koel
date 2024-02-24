<?php

namespace Tests;

use App\Facades\License;
use App\Services\License\CommunityLicenseService;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Tests\Traits\CreatesApplication;
use Tests\Traits\MakesHttpRequests;

abstract class TestCase extends BaseTestCase
{
    use ArraySubsetAsserts;
    use CreatesApplication;
    use DatabaseTransactions;
    use MakesHttpRequests;

    public function setUp(): void
    {
        parent::setUp();

        License::swap($this->app->make(CommunityLicenseService::class));
        self::createSandbox();
    }

    protected function tearDown(): void
    {
        self::destroySandbox();

        parent::tearDown();
    }

    private static function createSandbox(): void
    {
        config([
            'koel.album_cover_dir' => 'sandbox/img/covers/',
            'koel.artist_image_dir' => 'sandbox/img/artists/',
            'koel.playlist_cover_dir' => 'sandbox/img/playlists/',
        ]);

        File::ensureDirectoryExists(public_path(config('koel.album_cover_dir')));
        File::ensureDirectoryExists(public_path(config('koel.artist_image_dir')));
        File::ensureDirectoryExists(public_path(config('koel.playlist_cover_dir')));
        File::ensureDirectoryExists(public_path('sandbox/media/'));
    }

    private static function destroySandbox(): void
    {
        File::deleteDirectory(public_path('sandbox'));
    }
}
