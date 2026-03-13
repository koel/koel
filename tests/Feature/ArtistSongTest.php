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
        $artist = Artist::factory()->createOne();

        Song::factory()->for($artist)->createMany(5);

        $this->getAs("api/artists/{$artist->id}/songs")->assertJsonStructure([0 => SongResource::JSON_STRUCTURE]);
    }
}
