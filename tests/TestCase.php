<?php

namespace Tests;

use App\Facades\License;
use App\Helpers\Ulid;
use App\Helpers\Uuid;
use App\Models\Album;
use App\Services\License\CommunityLicenseService;
use App\Services\MediaBrowser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use Tests\Concerns\AssertsArraySubset;
use Tests\Concerns\CreatesApplication;
use Tests\Concerns\MakesHttpRequests;

abstract class TestCase extends BaseTestCase
{
    use AssertsArraySubset;
    use CreatesApplication;
    use LazilyRefreshDatabase;
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

        // During the Album's `saved` event, we attempt to generate a thumbnail by dispatching a job.
        // Disable this to avoid noise and side effects.
        Album::getEventDispatcher()?->forget('eloquent.saved: ' . Album::class);

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
            'koel.image_storage_dir' => 'sandbox/img/storage/',
            'koel.artifacts_path' => public_path('sandbox/artifacts/'),
        ]);

        File::ensureDirectoryExists(public_path(config('koel.image_storage_dir')));
        File::ensureDirectoryExists(public_path('sandbox/media/'));
    }

    private static function destroySandbox(): void
    {
        File::deleteDirectory(public_path('sandbox'));
    }
}
