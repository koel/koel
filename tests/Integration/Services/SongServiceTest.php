<?php

namespace Tests\Integration\Services;

use App\Events\LibraryChanged;
use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Jobs\DeleteTranscodeFilesJob;
use App\Jobs\ExtractSongFolderStructureJob;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\SongUpdateData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\test_path;

class SongServiceTest extends TestCase
{
    private SongService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(SongService::class);

        $user = create_user();
        $this->actingAs($user);

        Setting::set('media_path', $this->mediaPath);
    }

    public function testUpdateSingleSong(): void
    {
        Event::fake(LibraryChanged::class);

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

        DB::expects('transaction')->andReturnUsing(static function ($callback) {
            return $callback();
        });

        $updatedSongs = $this->service->updateSongs([$song->id], $data);

        $this->assertEquals(1, $updatedSongs->count());
        $this->assertEquals($expectedData['disc'], $updatedSongs->first()->disc);
        $this->assertEquals($expectedData['track'], $updatedSongs->first()->track);
        $this->assertEquals($expectedData['lyrics'], $updatedSongs->first()->lyrics);
        $this->assertEquals($expectedData['genre'], $updatedSongs->first()->genre);

        Event::assertDispatched(LibraryChanged::class);
    }

    public function testUpdateMultipleSongsTrackProvided(): void
    {
        Event::fake(LibraryChanged::class);

        /** @var Song $song1 */
        $song1 = Song::factory()->create([
            'track' => 1,
        ]);

        /** @var Song $song2 */
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

        DB::expects('transaction')->andReturnUsing(static function ($callback) {
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

        Event::assertDispatched(LibraryChanged::class);
    }

    public function testUpdateMultipleTracksWithoutProvidingTrack(): void
    {
        Event::fake(LibraryChanged::class);

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

        DB::expects('transaction')->andReturnUsing(static function ($callback) {
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

        Event::assertDispatched(LibraryChanged::class);
    }

    #[Test]
    public function deleteSongs(): void
    {
        Event::fake(LibraryChanged::class);
        $songs = Song::factory()->count(2)->create();

        Dispatcher::expects('dispatch')
            ->with(DeleteSongFilesJob::class)
            ->andReturnUsing(static function (DeleteSongFilesJob $job) use ($songs): void {
                self::assertEqualsCanonicalizing(
                    $job->files->pluck('location')->toArray(),
                    $songs->pluck('path')->toArray(),
                );
            });

        Dispatcher::expects('dispatch')->with(DeleteTranscodeFilesJob::class)->never();

        $this->service->deleteSongs($songs->pluck('id')->toArray());
        $songs->each(fn (Song $song) => $this->assertDatabaseMissing(Song::class, ['id' => $song->id]));

        Event::assertDispatched(LibraryChanged::class);
    }

    #[Test]
    public function deleteSongsWithTranscodes(): void
    {
        Event::fake(LibraryChanged::class);

        $transcodes = Transcode::factory()->count(2)->create();
        $songs = $transcodes->map(static fn (Transcode $transcode) => $transcode->song); // @phpstan-ignore-line

        Dispatcher::expects('dispatch')
            ->with(DeleteSongFilesJob::class)
            ->andReturnUsing(static function (DeleteSongFilesJob $job) use ($songs): void {
                self::assertEqualsCanonicalizing(
                    $job->files->pluck('location')->toArray(),
                    $songs->pluck('path')->toArray(),
                );
            });

        Dispatcher::expects('dispatch')
            ->with(DeleteTranscodeFilesJob::class)
            ->andReturnUsing(static function (DeleteTranscodeFilesJob $job) use ($transcodes): void {
                self::assertEqualsCanonicalizing(
                    $job->files->pluck('location')->toArray(),
                    $transcodes->pluck('location')->toArray(),
                );
            });

        $this->service->deleteSongs($transcodes->pluck('song_id')->toArray());

        $transcodes->each(function (Transcode $transcode): void {
            $this->assertDatabaseMissing(Song::class, ['id' => $transcode->song_id]);
            $this->assertDatabaseMissing(Transcode::class, ['id' => $transcode->id]);
        });

        Event::assertDispatched(LibraryChanged::class);
    }

    #[Test]
    public function createOrUpdateFromScan(): void
    {
        Dispatcher::expects('dispatch')->with(ExtractSongFolderStructureJob::class);

        $info = app(FileScanner::class)->scan(test_path('songs/full.mp3'));
        $song = $this->service->createOrUpdateSongFromScan($info, ScanConfiguration::make(owner: create_admin()));

        self::assertArraySubset([
            'title' => 'Amet',
            'track' => 5,
            'disc' => 3,
            'lyrics' => "Foo\rbar",
            'mtime' => filemtime(test_path('songs/full.mp3')),
            'year' => 2015,
            'is_public' => false,
            'artist_name' => 'Koel',
            'album_name' => 'Koel Testing Vol. 1',
        ], $song->getAttributes());

        self::assertSame(2015, $song->album->year);
    }

    #[Test]
    public function creatingOrUpdatingFromScanSetsAlbumReleaseYearIfApplicable(): void
    {
        Dispatcher::expects('dispatch')->with(ExtractSongFolderStructureJob::class);

        $owner = create_admin();

        /** @var Artist $artist */
        $artist = Artist::factory(['name' => 'Koel'])->for($owner)->create();

        /** @var Album $album */
        $album = Album::factory([
            'name' => 'Koel Testing Vol. 1',
            'year' => null,
        ])
            ->for($owner)
            ->for($artist)
            ->create();

        self::assertNull($album->year);

        $info = app(FileScanner::class)->scan(test_path('songs/full.mp3'));
        $this->service->createOrUpdateSongFromScan($info, ScanConfiguration::make(owner: $owner));

        self::assertSame(2015, $album->refresh()->year);
    }

    #[Test]
    public function creatingOrUpdatingFromScanSetsAlbumReleaseYearIfItAlreadyExists(): void
    {
        Dispatcher::expects('dispatch')->with(ExtractSongFolderStructureJob::class);

        $owner = create_admin();

        /** @var Artist $artist */
        $artist = Artist::factory(['name' => 'Koel'])->for($owner)->create();

        /** @var Album $album */
        $album = Album::factory([
            'name' => 'Koel Testing Vol. 1',
            'year' => 2018,
        ])
            ->for($owner)
            ->for($artist)
            ->create();

        $info = app(FileScanner::class)->scan(test_path('songs/full.mp3'));
        $song = $this->service->createOrUpdateSongFromScan($info, ScanConfiguration::make(owner: $owner));

        self::assertTrue($song->album->is($album));

        self::assertSame(2018, $album->refresh()->year);
    }
}
