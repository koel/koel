<?php

namespace Tests\Integration\Models;

use App\Models\Artist;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    /** @test */
    public function existing_artist_can_be_retrieved_using_name()
    {
        // Given an existing artist with a name
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create(['name' => 'Foo']);

        // When I get the artist by name
        $gottenArtist = Artist::get('Foo');

        // Then I get the artist
        $this->assertEquals($artist->id, $gottenArtist->id);
    }

    /** @test */
    public function new_artist_can_be_created_using_name()
    {
        // Given an artist name
        $name = 'Foo';

        // And an artist with such a name doesn't exist yet
        $this->assertNull(Artist::whereName($name)->first());

        // When I get the artist by name
        $artist = Artist::get($name);

        // Then I get the newly created artist
        $this->assertInstanceOf(Artist::class, $artist);
    }

    /** @test */
    public function getting_artist_with_empty_name_returns_unknown_artist()
    {
        // Given an empty name
        $name = '';

        // When I get the artist by the empty name
        $artist = Artist::get($name);

        // Then I get the artist as Unknown Artist
        $this->assertTrue($artist->is_unknown);
    }

    /** @test */
    public function artists_with_name_in_utf16_encoding_are_retrieved_correctly()
    {
        // Given there's an artist with name in UTF-16 encoding
        $name = file_get_contents(__DIR__.'../../../blobs/utf16');
        $artist = Artist::get($name);

        // When I get the artist using the name
        $retrieved = Artist::get($name);

        // Then I receive the artist
        $this->assertEquals($artist->id, $retrieved->id);
    }
}
