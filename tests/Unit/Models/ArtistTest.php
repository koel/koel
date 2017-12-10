<?php

namespace Tests\Unit\Models;

use App\Models\Artist;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $this->assertInstanceOf(Artist::class, new Artist());
    }

    /** @test */
    public function various_artist_can_be_retrieved()
    {
        // When I retrieve the Various Artist
        $artist = Artist::getVariousArtist();

        // Then I received the Various Artist
        $this->assertTrue($artist->is_various);
    }
}
