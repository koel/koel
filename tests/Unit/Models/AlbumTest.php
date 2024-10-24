<?php

namespace Tests\Unit\Models;

use App\Models\Album;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    #[Test]
    public function existingAlbumCanBeRetrievedUsingArtistAndName(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        self::assertTrue(Album::getOrCreate($album->artist, $album->name)->is($album));
    }

    #[Test]
    public function newAlbumIsAutomaticallyCreatedWithArtistAndName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $name = 'Foo';

        self::assertNull(Album::query()->where('artist_id', $artist->id)->where('name', $name)->first());

        $album = Album::getOrCreate($artist, $name);
        self::assertSame('Foo', $album->name);
        self::assertTrue($album->artist->is($artist));
    }

    /** @return array<mixed> */
    public static function provideEmptyAlbumNames(): array
    {
        return [
            [''],
            ['  '],
            [null],
            [false],
        ];
    }

    #[DataProvider('provideEmptyAlbumNames')]
    #[Test]
    public function newAlbumWithoutNameIsCreatedAsUnknownAlbum($name): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $album = Album::getOrCreate($artist, $name);

        self::assertSame('Unknown Album', $album->name);
    }
}
