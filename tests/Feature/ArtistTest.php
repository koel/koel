<?php

namespace Tests\Feature;

use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    public function testIndex(): void
    {
        Artist::factory(10)->create();

        $this->getAs('api/artists')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);
    }

    public function testShow(): void
    {
        $this->getAs('api/artists/' . Artist::factory()->create()->id)
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE);
    }
}
