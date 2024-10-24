<?php

namespace Tests\Feature;

use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    #[Test]
    public function index(): void
    {
        Artist::factory(10)->create();

        $this->getAs('api/artists')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function show(): void
    {
        $this->getAs('api/artists/' . Artist::factory()->create()->id)
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE);
    }
}
