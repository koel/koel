<?php

namespace Tests;

use App\Facades\License;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Services\CommunityLicenseService;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\TestResponse;
use ReflectionClass;
use Tests\Traits\CreatesApplication;
use Tests\Traits\SandboxesTests;

abstract class TestCase extends BaseTestCase
{
    use ArraySubsetAsserts;
    use CreatesApplication;
    use DatabaseTransactions;
    use SandboxesTests;

    public function setUp(): void
    {
        parent::setUp();

        License::swap($this->app->make(CommunityLicenseService::class));

        TestResponse::macro('log', function (string $file = 'test-response.json'): TestResponse {
            /** @var TestResponse $this */
            File::put(storage_path('logs/' . $file), $this->getContent());

            return $this;
        });

        UploadedFile::macro('fromFile', static function (string $path, ?string $name = null): UploadedFile {
            return UploadedFile::fake()->createWithContent($name ?? basename($path), File::get($path));
        });

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
}
