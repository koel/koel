<?php

namespace Tests\Feature\V6;

use App\Models\Song;

class SongTest extends TestCase
{
    public const JSON_STRUCTURE = [
        'type',
        'id',
        'title',
        'lyrics',
        'album_id',
        'album_name',
        'artist_id',
        'artist_name',
        'album_artist_id',
        'album_artist_name',
        'album_cover',
        'length',
        'liked',
        'play_count',
        'track',
        'disc',
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
        Song::factory(10)->create();

        $this->getAs('api/songs')->assertJsonStructure(self::JSON_COLLECTION_STRUCTURE);
        $this->getAs('api/songs?sort=title&order=desc')->assertJsonStructure(self::JSON_COLLECTION_STRUCTURE);
    }

    public function testShow(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->getAs('api/songs/' . $song->id)->assertJsonStructure(self::JSON_STRUCTURE);
    }
}
