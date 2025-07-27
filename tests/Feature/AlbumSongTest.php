<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Album;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumSongTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        Song::factory(5)->for($album)->create();

        $this->getAs("api/albums/{$album->id}/songs")
            ->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }
}
