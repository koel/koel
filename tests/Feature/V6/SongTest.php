<?php

namespace Tests\Feature\V6;

use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;

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
        'genre',
        'year',
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

    public function testDelete(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory(3)->create();

        /** @var User $admin */
        $admin = User::factory()->admin()->create();

        $this->deleteAs('api/songs', ['songs' => $songs->pluck('id')->toArray()], $admin)
            ->assertNoContent();

        $songs->each(fn (Song $song) => $this->assertModelMissing($song));
    }

    public function testUnauthorizedDelete(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory(3)->create();

        $this->deleteAs('api/songs', ['songs' => $songs->pluck('id')->toArray()])
            ->assertForbidden();

        $songs->each(fn (Song $song) => $this->assertModelExists($song));
    }
}
