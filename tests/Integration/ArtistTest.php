<?php

namespace Tests\Integration;

use App\Models\Artist;
use Lastfm;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    /** @test */
    public function extra_info_can_be_retrieved_for_an_artist()
    {
        // Given there's an artist
        /** @var Artist $artist */
        $artist = factory(Artist::class)->create();

        // When I get the extra info
        Lastfm::shouldReceive('getArtistInfo')
            ->once()
            ->with($artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $artist->getInfo();

        // Then I receive the extra info
        $this->assertEquals(['foo' => 'bar'], $info);
    }
}
