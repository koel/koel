<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    public function testIndex(): void
    {
        Album::factory(10)->create();

        $this->getAs('api/albums')
            ->assertJsonStructure(AlbumResource::PAGINATION_JSON_STRUCTURE);
    }

    public function testShow(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->getAs('api/albums/' . $album->id)
            ->assertJsonStructure(AlbumResource::JSON_STRUCTURE);
    }
}
