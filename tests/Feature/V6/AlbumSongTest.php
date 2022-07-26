<?php

namespace Tests\Feature\V6;

use App\Models\Album;
use App\Models\Song;

class AlbumSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        Song::factory(5)->create([
            'album_id' => $album->id,
        ]);

        $this->getAsUser('api/albums/' . $album->id . '/songs')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
