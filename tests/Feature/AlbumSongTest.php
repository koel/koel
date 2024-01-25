<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Song;
use Tests\TestCase;

class AlbumSongTest extends TestCase
{
    public function testIndex(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        Song::factory(5)->for($album)->create();

        $this->getAs('api/albums/' . $album->id . '/songs')
            ->assertJsonStructure(['*' => SongResource::JSON_STRUCTURE]);
    }
}
