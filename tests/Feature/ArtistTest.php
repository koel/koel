<?php

namespace Tests\Feature;

use App\Models\Artist;
use Tests\TestCase;

class ArtistTest extends TestCase
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'image',
        'created_at',
    ];

    private const JSON_COLLECTION_STRUCTURE = [
        'data' => [
            '*' => self::JSON_STRUCTURE,
        ],
        'links' => [
            'first',
            'last',
            'prev',
            'next',
        ],
        'meta' => [
            'current_page',
            'from',
            'path',
            'per_page',
            'to',
        ],
    ];

    public function testIndex(): void
    {
        Artist::factory(10)->create();

        $this->getAs('api/artists')
            ->assertJsonStructure(self::JSON_COLLECTION_STRUCTURE);
    }

    public function testShow(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->getAs('api/artists/' . $artist->id)
            ->assertJsonStructure(self::JSON_STRUCTURE);
    }
}
