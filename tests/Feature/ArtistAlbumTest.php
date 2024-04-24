<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use App\Models\Artist;
use Tests\TestCase;

class ArtistAlbumTest extends TestCase
{
    public function testIndex(): void
    {
        $artist = Artist::factory()->create();

        Album::factory(5)->for($artist)->create();

        $this->getAs('api/artists/' . $artist->id . '/albums')
            ->assertJsonStructure(['*' => AlbumResource::JSON_STRUCTURE]);
    }
}
