<?php

namespace Tests\Integration;

use App\Models\Album;
use Lastfm;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    /** @test */
    public function extra_info_can_be_retrieved_for_an_album()
    {
        // Given there's an album
        /** @var Album $album */
        $album = factory(Album::class)->create();

        // When I get the extra info for the album
        Lastfm::shouldReceive('getAlbumInfo')
            ->once()
            ->with($album->name, $album->artist->name)
            ->andReturn(['foo' => 'bar']);

        $info = $album->getInfo();

        // Then I receive the extra info
        $this->assertEquals(['foo' => 'bar'], $info);
    }
}
