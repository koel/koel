<?php

namespace Tests\Feature;

use App\Http\Resources\SongResource;
use App\Models\Artist;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistSongTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        Song::factory(5)->for($artist)->create();

        $this->getAs("api/artists/{$artist->id}/songs")
            ->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }
}
