<?php

namespace Tests\Feature\V6;

use App\Models\Album;

class AlbumTest extends TestCase
{
    private const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'artist_id',
        'artist_name',
        'cover',
        'created_at',
        'length',
        'play_count',
        'song_count',
    ];

    private const JSON_COLLECTION_STRUCTURE = [
        'data' => [
            '*' => self::JSON_STRUCTURE,
        ],
    ];

    public function testIndex(): void
    {
        Album::factory(10)->create();

        $this->getAsUser('api/albums')
            ->assertJsonStructure(self::JSON_COLLECTION_STRUCTURE);
    }

    public function testShow(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->getAsUser('api/albums/' . $album->id)
            ->assertJsonStructure(self::JSON_STRUCTURE);
    }
}
