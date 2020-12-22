<?php

namespace Tests;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use ReflectionClass;
use Tests\Traits\CreatesApplication;
use Tests\Traits\InteractsWithIoc;
use Tests\Traits\SandboxesTests;

abstract class TestCase extends BaseTestCase
{
    use ArraySubsetAsserts;
    use CreatesApplication;
    use DatabaseTransactions;
    use InteractsWithIoc;
    use SandboxesTests;

    public function setUp(): void
    {
        parent::setUp();

        $this->prepareForTests();
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
        $albums = Album::factory(3)->create([
            'artist_id' => $artist->id,
        ]);

        // 7-15 songs per albums
        foreach ($albums as $album) {
            Song::factory(random_int(7, 15))->create([
                'album_id' => $album->id,
                'artist_id' => $artist->id,
            ]);
        }
    }

    /** @return mixed */
    protected static function getNonPublicProperty($object, string $property)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
