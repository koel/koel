<?php

namespace Tests\Integration\Services;

use App\Models\Song;
use App\Services\SongService;
use App\Values\SongUpdateData;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

use function Tests\create_user;

class SongServiceTest extends TestCase
{
    private SongService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SongService::class);

        $user = create_user();
        $this->actingAs($user);
    }

    public function testUpdateSingleSong(): void
    {
        $song = Song::factory()->create();

        $data = SongUpdateData::factory()->make([
            'disc' => null,
            'track' => null,
            'lyrics' => null,
            'year' => null,
            'genre' => null,
            'albumArtistName' => null,
            'artistName' => 'Artist A',
        ]);

        $expectedData = [
            'disc' => 1,
            'track' => 0,
            'lyrics' => '',
            'year' => null,
            'genre' => '',
            'albumArtistName' => 'Artist A',
        ];

        DB::shouldReceive('transaction')->andReturnUsing(static function ($callback) {
            return $callback();
        });
        
        $updatedSongs = $this->service->updateSongs([$song->id], $data);

        $this->assertEquals(1, $updatedSongs->count());
        $this->assertEquals($expectedData['disc'], $updatedSongs->first()->disc);
        $this->assertEquals($expectedData['track'], $updatedSongs->first()->track);
        $this->assertEquals($expectedData['lyrics'], $updatedSongs->first()->lyrics);
        $this->assertEquals($expectedData['genre'], $updatedSongs->first()->genre);
    }

    public function testUpdateMultipleSongsWithAllFieldsProvided(): void
    {
        $song1 = Song::factory()->create();
        $song2 = Song::factory()->create();

        $data = SongUpdateData::factory()->make([
        'disc' => 2,
        'track' => 5,
        'lyrics' => 'New lyrics',
        'year' => 2023,
        'genre' => 'Pop',
        'albumArtistName' => 'New Album Artist',
        'artistName' => 'Artist B',
        ]);

        $expectedData = [
        'disc' => 2,
        'track' => 5,
        'lyrics' => 'New lyrics',
        'year' => 2023,
        'genre' => 'Pop',
        'albumArtistName' => 'New Album Artist',
        ];

        DB::shouldReceive('transaction')->andReturnUsing(static function ($callback) {
            return $callback();
        });

        $updatedSongs = $this->service->updateSongs([$song1->id, $song2->id], $data);

        $this->assertEquals(2, $updatedSongs->count());

        foreach ($updatedSongs as $updatedSong) {
            $this->assertEquals($expectedData['disc'], $updatedSong->disc);
            $this->assertEquals($expectedData['track'], $updatedSong->track);
            $this->assertEquals($expectedData['lyrics'], $updatedSong->lyrics);
            $this->assertEquals($expectedData['genre'], $updatedSong->genre);
        }
    }
}
