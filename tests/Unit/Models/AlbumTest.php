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
        $artist = Artist::factory()->createOne();
        $album = Album::factory()->for($artist)->for($artist->user)->createOne();

        self::assertTrue(Album::getOrCreate($artist, $album->name)->is($album));
    }

    #[Test]
    public function newAlbumIsAutomaticallyCreatedWithUserAndArtistAndName(): void
    {
        $artist = Artist::factory()->createOne();
        $name = 'Foo';

        self::assertNull(Album::query()->whereBelongsTo($artist)->where('name', $name)->first());

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
        $artist = Artist::factory()->createOne();

        $album = Album::getOrCreate($artist, $name);

        self::assertSame('Unknown Album', $album->name);
    }

    /** @return array<string, array{string, bool}> */
    public static function provideIsStandardData(): array
    {
        return [
            'standard album' => ['Master of Puppets', true],
            'Unknown Album' => [Album::UNKNOWN_NAME, false],
        ];
    }

    #[Test]
    #[DataProvider('provideIsStandardData')]
    public function onlyAlbumsOtherThanUnknownAreStandard(string $name, bool $standard): void
    {
        self::assertSame($standard, Album::factory()->createOne(['name' => $name])->is_standard);
    }

    /** @return array<string, array{string, string, bool}> */
    public static function provideIsIdentifiableData(): array
    {
        return [
            'known album and artist' => ['Master of Puppets', 'Metallica', true],
            'compilation' => ['Now 100', Artist::VARIOUS_NAME, true],
            'Unknown Album' => [Album::UNKNOWN_NAME, 'Metallica', false],
            'Unknown Artist' => ['Master of Puppets', Artist::UNKNOWN_NAME, false],
        ];
    }

    #[Test]
    #[DataProvider('provideIsIdentifiableData')]
    public function onlyAlbumsWithAKnownNameAndArtistAreIdentifiable(
        string $albumName,
        string $artistName,
        bool $identifiable,
    ): void {
        $album = Album::factory()->for(Artist::factory()->createOne(['name' => $artistName]))->createOne([
            'name' => $albumName,
        ]);

        self::assertSame($identifiable, $album->is_identifiable);
    }
}
