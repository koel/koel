<?php

namespace Tests\Integration\Services;

use App\Jobs\DeleteSongFiles;
use App\Jobs\DeleteTranscodeFiles;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\SongService;
use App\Values\SongUpdateData;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
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

        $data = SongUpdateData::make(
            title: null,
            artistName: null,
            albumName: null,
            albumArtistName: 'Artist A',
            track: null,
            disc: 1,
            genre: null,
            year: null,
            lyrics: null
        );

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

    public function testUpdateMultipleSongsTrackProvided(): void
    {
        $song1 = Song::factory()->create([
            'track' => 1,
        ]);
        $song2 = Song::factory()->create([
            'track' => 2,
        ]);

        $albumArtistName = 'New Album Artist';
        $lyrics = 'Lyrics 2';
        $data = SongUpdateData::make(
            title: null,
            artistName: 'Arist B',
            albumName: null,
            albumArtistName: $albumArtistName,
            track: 5,
            disc: 2,
            genre: 'Pop',
            year: 2023,
            lyrics: $lyrics
        );

        $expectedData = [
            'disc' => 2,
            'track' => 5,
            'lyrics' => $lyrics,
            'year' => 2023,
            'genre' => 'Pop',
            'albumArtistName' => $albumArtistName,
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

    public function testUpdateMultipleTracksWithoutProvidingTrack(): void
    {
        $song1 = Song::factory()->create(['track' => 1, 'disc' => 1]);
        $song2 = Song::factory()->create(['track' => 2, 'disc' => 1]);

        $lyrics = 'Lyrics';
        $genre = 'Genre';

        $data = SongUpdateData::make(
            title: null,
            artistName: 'Artist',
            albumName: null,
            albumArtistName: null,
            track: null,
            disc: null,
            genre: 'Genre',
            year: null,
            lyrics: $lyrics
        );


        $expectedData1 = [
            'disc' => 1,
            'track' => 1,
            'lyrics' => $lyrics,
            'year' => null,
            'genre' => $genre,
            'albumArtistName' => null,
        ];

        $expectedData2 = [
            'disc' => 1,
            'track' => 2,
            'lyrics' => $lyrics,
            'year' => null,
            'genre' => $genre,
            'albumArtistName' => null,
        ];

        DB::shouldReceive('transaction')->andReturnUsing(static function ($callback) {
            return $callback();
        });

        $updatedSongs = $this->service->updateSongs([$song1->id, $song2->id], $data);

        $this->assertEquals(2, $updatedSongs->count());

        $this->assertEquals($expectedData1['disc'], $updatedSongs[0]->disc);
        $this->assertEquals($expectedData1['track'], $updatedSongs[0]->track);
        $this->assertEquals($expectedData1['lyrics'], $updatedSongs[0]->lyrics);
        $this->assertEquals($expectedData1['genre'], $updatedSongs[0]->genre);

        $this->assertEquals($expectedData2['disc'], $updatedSongs[1]->disc);
        $this->assertEquals($expectedData2['track'], $updatedSongs[1]->track);
        $this->assertEquals($expectedData2['lyrics'], $updatedSongs[1]->lyrics);
        $this->assertEquals($expectedData2['genre'], $updatedSongs[1]->genre);
    }

    #[Test]
    public function deleteSongs(): void
    {
        Bus::fake();
        $songs = Song::factory()->count(3)->create();

        $this->service->deleteSongs($songs->pluck('id')->toArray());

        $songs->each(fn (Song $song) => $this->assertDatabaseMissing(Song::class, ['id' => $song->id]));

        Bus::assertDispatched(
            DeleteSongFiles::class,
            static function (DeleteSongFiles $job) use ($songs) {
                self::assertEqualsCanonicalizing(
                    $job->files->pluck('location')->toArray(),
                    $songs->pluck('path')->toArray(),
                );

                return true;
            }
        );

        Bus::assertNotDispatched(DeleteTranscodeFiles::class);
    }

    #[Test]
    public function deleteSongsWithTranscodes(): void
    {
        Bus::fake();
        $transcodes = Transcode::factory()->count(3)->create();
        $songs = $transcodes->map(static fn (Transcode $transcode) => $transcode->song); // @phpstan-ignore-line

        $this->service->deleteSongs($transcodes->pluck('song_id')->toArray());

        $transcodes->each(function (Transcode $transcode): void {
            $this->assertDatabaseMissing(Song::class, ['id' => $transcode->song_id]);
            $this->assertDatabaseMissing(Transcode::class, ['id' => $transcode->id]);
        });

        Bus::assertDispatched(
            DeleteSongFiles::class,
            static function (DeleteSongFiles $job) use ($songs) {
                self::assertEqualsCanonicalizing(
                    $job->files->pluck('location')->toArray(),
                    $songs->pluck('path')->toArray(),
                );

                return true;
            }
        );

        Bus::assertDispatched(
            DeleteTranscodeFiles::class,
            static function (DeleteTranscodeFiles $job) use ($transcodes) {
                self::assertEqualsCanonicalizing(
                    $job->files->pluck('location')->toArray(),
                    $transcodes->pluck('location')->toArray(),
                );

                return true;
            }
        );
    }
}
