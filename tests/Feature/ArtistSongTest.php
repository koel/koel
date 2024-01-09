<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Models\Song;
use Tests\TestCase;

class ArtistSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        Song::factory(5)->for($artist)->create();

        $this->getAs('api/artists/' . $artist->id . '/songs')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
