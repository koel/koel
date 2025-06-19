<?php

namespace Tests;

use App\Facades\License;
use App\Helpers\Ulid;
use App\Helpers\Uuid;
use App\Services\License\CommunityLicenseService;
use App\Services\MediaBrowser;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Filesystem\Filesystem;
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

    /**
     * @var Filesystem The backup of the real filesystem instance, to restore after tests.
     * This is necessary because we might be mocking the File facade in tests, and at the same time
     * we delete test resources during suite's teardown.
     */
    private Filesystem $fileSystem;

    public function setUp(): void
    {
        parent::setUp();

        License::swap($this->app->make(CommunityLicenseService::class));
        $this->fileSystem = File::getFacadeRoot();

        self::createSandbox();
    }

    protected function tearDown(): void
    {
        File::swap($this->fileSystem);
        self::destroySandbox();
        MediaBrowser::clearCache();

        Ulid::unfreeze();
        Uuid::unfreeze();

        parent::tearDown();
    }

    private static function createSandbox(): void
    {
        config([
            'koel.album_cover_dir' => 'sandbox/img/covers/',
            'koel.artist_image_dir' => 'sandbox/img/artists/',
            'koel.playlist_cover_dir' => 'sandbox/img/playlists/',
            'koel.user_avatar_dir' => 'sandbox/img/avatars/',
            'koel.artifacts_path' => public_path('sandbox/artifacts/'),
        ]);

        File::ensureDirectoryExists(public_path(config('koel.album_cover_dir')));
        File::ensureDirectoryExists(public_path(config('koel.artist_image_dir')));
        File::ensureDirectoryExists(public_path(config('koel.playlist_cover_dir')));
        File::ensureDirectoryExists(public_path(config('koel.user_avatar_dir')));
        File::ensureDirectoryExists(public_path('sandbox/media/'));
    }

    private static function destroySandbox(): void
    {
        File::deleteDirectory(public_path('sandbox'));
    }
}
