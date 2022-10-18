<?php

namespace Tests\Feature\V6;

use App\Models\Album;
use App\Models\Artist;

class ArtistAlbumTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        Album::factory(5)->for($artist)->create();

        $this->getAs('api/artists/' . $artist->id . '/albums')
            ->assertJsonStructure(['*' => AlbumTest::JSON_STRUCTURE]);
    }
}
