<?php

namespace Tests\Feature\V6;

use App\Models\Album;
use App\Models\Song;

class AlbumSongTest extends TestCase
{
    private const JSON_STRUCTURE = [
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
        '*' => self::JSON_STRUCTURE,
    ];

    public function testIndex(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        Song::factory(5)->create([
            'album_id' => $album->id,
        ]);

        $this->getAsUser('api/albums/' . $album->id . '/songs')
            ->assertJsonStructure(self::JSON_COLLECTION_STRUCTURE);
    }
}
