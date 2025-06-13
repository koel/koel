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

        $this->getAs('api/artists?sort=name&order=asc')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);

        $this->getAs('api/artists?sort=created_at&order=desc&page=2')
            ->assertJsonStructure(ArtistResource::PAGINATION_JSON_STRUCTURE);
    }

    #[Test]
    public function show(): void
    {
        $this->getAs('api/artists/' . Artist::factory()->create()->public_id)
            ->assertJsonStructure(ArtistResource::JSON_STRUCTURE);
    }
}
