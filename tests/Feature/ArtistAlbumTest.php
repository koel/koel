<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistAlbumTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        $artist = Artist::factory()->createOne();
        Album::factory()->for($artist)->createMany(5);

        $this->getAs("api/artists/{$artist->id}/albums")->assertJsonStructure([0 => AlbumResource::JSON_STRUCTURE]);
    }
}
