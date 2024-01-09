<?php

namespace Tests;

use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\License\CommunityLicenseService;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;
use ReflectionClass;
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

    protected static function createSampleMediaSet(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        /** @var array<Album> $albums */
        $albums = Album::factory(3)->for($artist)->create();

        // 7-15 songs per albums
        foreach ($albums as $album) {
            Song::factory(random_int(7, 15))->for($artist)->for($album)->create();
        }
    }

    protected static function getNonPublicProperty($object, string $property): mixed
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

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
