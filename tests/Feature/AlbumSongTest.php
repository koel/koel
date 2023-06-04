<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Song;

class AlbumSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        Song::factory(5)->for($album)->create();

        $this->getAs('api/albums/' . $album->id . '/songs')
            ->assertJsonStructure(['*' => SongTest::JSON_STRUCTURE]);
    }
}
