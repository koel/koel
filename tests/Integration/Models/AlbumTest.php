<?php

namespace Tests\Integration\Models;

use App\Models\Album;
use App\Models\Artist;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    public function testExistingAlbumCanBeRetrievedUsingArtistAndName(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        self::assertTrue(Album::getOrCreate($album->artist, $album->name)->is($album));
    }

    public function testNewAlbumIsAutomaticallyCreatedWithArtistAndName(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $name = 'Foo';

        self::assertNull(Album::whereArtistIdAndName($artist->id, $name)->first());

        $album = Album::getOrCreate($artist, $name);
        self::assertSame('Foo', $album->name);
        self::assertTrue($album->artist->is($artist));
    }

    public function provideEmptyAlbumNames(): array
    {
        return [
            [''],
            ['  '],
            [null],
            [false],
        ];
    }

    /** @dataProvider provideEmptyAlbumNames */
    public function testNewAlbumWithoutNameIsCreatedAsUnknownAlbum($name): void
    {
        self::assertEquals('Unknown Album', Album::getOrCreate(Artist::factory()->create(), $name)->name);
    }

    public function testNewAlbumIsCreatedWithArtistAsVariousIfIsCompilationFlagIsTrue(): void
    {
        self::assertTrue(Album::getOrCreate(Artist::factory()->create(), 'Foo', true)->artist->is_various);
    }
}
