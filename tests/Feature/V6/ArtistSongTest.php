<?php

namespace Tests\Feature\V6;

use App\Models\Artist;
use App\Models\Song;

class ArtistSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        Song::factory(5)->create([
            'artist_id' => $artist->id,
        ]);

        $this->getAsUser('api/artists/' . $artist->id . '/songs')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
