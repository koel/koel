<?php

namespace Tests\Feature;

use App\Models\Album;
use Tests\TestCase;

class AlbumTest extends TestCase
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'artist_id',
        'artist_name',
        'cover',
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
        Album::factory(10)->create();

        $this->getAs('api/albums')
            ->assertJsonStructure(self::JSON_COLLECTION_STRUCTURE);
    }

    public function testShow(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->getAs('api/albums/' . $album->id)
            ->assertJsonStructure(self::JSON_STRUCTURE);
    }
}
