<?php

namespace Tests\Integration\Models;

use App\Models\Album;
use App\Models\Artist;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    /** @test */
    public function exist_album_can_be_retrieved_using_artist_and_name()
    {
        // Given there's an existing album from an artist
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();
        /** @var Album $album */
        $album = factory(Album::class)->create([
            'artist_id' => $artist->id,
        ]);

        // When I try to get the album by artist and name
        $gottenAlbum = Album::get($artist, $album->name);

        // Then I get the album
        self::assertSame($album->id, $gottenAlbum->id);
    }

    /** @test */
    public function new_album_can_be_created_using_artist_and_name()
    {
        // Given an artist and an album name
        $artist = factory(Artist::class)->create();
        $name = 'Foo';

        // And an album with such details doesn't exist yet
        self::assertNull(Album::whereArtistIdAndName($artist->id, $name)->first());

        // When I try to get the album by such artist and name
        $album = Album::get($artist, $name);

        // Then I get the new album
        self::assertNotNull($album);
    }

    /** @test */
    public function new_album_without_a_name_is_created_as_unknown_album()
    {
        // Given an album without a name
        $name = '';

        // When we create such an album
        $album = Album::get(factory(Artist::class)->create(), $name);

        // Then the album's name is "Unknown Album"
        self::assertEquals('Unknown Album', $album->name);
    }

    /** @test */
    public function new_album_is_created_with_artist_as_various_if_is_compilation_flag_is_true()
    {
        // Given we create a new album with $isCompilation flag set to TRUE
        $isCompilation = true;

        // When the album is created
        $album = Album::get(factory(Artist::class)->create(), 'Foo', $isCompilation);

        // Then its artist is Various Artist
        self::assertTrue($album->artist->is_various);
    }
}
