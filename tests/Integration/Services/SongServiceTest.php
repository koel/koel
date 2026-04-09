<?php

namespace Tests\Integration\Services;

use App\Facades\Dispatcher;
use App\Jobs\DeleteSongFilesJob;
use App\Jobs\DeleteTranscodeFilesJob;
use App\Jobs\ExtractSongFolderStructureJob;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Models\Transcode;
use App\Services\AlbumService;
use App\Services\Scanners\FileScanner;
use App\Services\SongService;
use App\Values\Scanning\ScanConfiguration;
use App\Values\Song\SongUpdateData;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
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

    #[Test]
    public function updateSingleSong(): void
    {
        $song = Song::factory()->createOne();

        $data = SongUpdateData::make(albumArtistName: 'Queen', disc: 1, lyrics: 'Is this the real life?');

        $result = $this->service->updateSongs([$song->id], $data);
        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        self::assertCount(1, $result->updatedSongs);
        self::assertEquals(1, $updatedSong->disc);
        self::assertEquals(0, $updatedSong->track);
        self::assertEquals('Is this the real life?', $updatedSong->lyrics);
        self::assertEquals('', $updatedSong->genre);
        self::assertEquals('Queen', $updatedSong->album_artist->name);
        // We changed the album artist name, so the old album should have been removed
        self::assertCount(1, $result->removedAlbumIds);
    }

    #[Test]
    public function updateSingleSongWithAlbumChanged(): void
    {
        $song = Song::factory()->createOne();
        $artist = $song->artist;
        $album = $song->album;

        $data = SongUpdateData::make(albumName: 'New Album');

        $result = $this->service->updateSongs([$song->id], $data);

        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        self::assertTrue($updatedSong->artist->is($artist));
        self::assertFalse($updatedSong->album->is($album));
        self::assertSame('New Album', $updatedSong->album->name);
        self::assertSame('New Album', $updatedSong->album_name);

        // The old album should have been removed
        self::assertCount(1, $result->removedAlbumIds);
        self::assertSame($album->id, $result->removedAlbumIds->first());
    }

    #[Test]
    public function updateSongWithArtistChanged(): void
    {
        $song = Song::factory()->createOne();
        $artist = $song->artist;
        $album = $song->album;
        $albumName = $song->album->name;

        $data = SongUpdateData::make(artistName: 'New Artist');

        $result = $this->service->updateSongs([$song->id], $data);

        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        self::assertFalse($updatedSong->artist->is($artist));
        self::assertSame('New Artist', $updatedSong->artist->name);
        self::assertSame('New Artist', $updatedSong->artist_name);

        // The album changes to a new one with the same name
        self::assertFalse($updatedSong->album->is($album));
        self::assertTrue($updatedSong->album->artist->is($updatedSong->artist));
        self::assertSame($albumName, $updatedSong->album->name);

        // Old album and artist should have been removed
        self::assertCount(1, $result->removedAlbumIds);
        self::assertCount(1, $result->removedArtistIds);
        self::assertSame($album->id, $result->removedAlbumIds->first());
        self::assertSame($artist->id, $result->removedArtistIds->first());
    }

    #[Test]
    public function updateMultipleSongsTrackProvided(): void
    {
        $song1 = Song::factory()->createOne(['track' => 1]);
        $song2 = Song::factory()->createOne(['track' => 2]);

        $data = SongUpdateData::make(
            artistName: 'Queen',
            albumArtistName: 'New Album Artist',
            track: 5,
            disc: 2,
            genre: 'Pop',
            year: 2023,
            lyrics: 'Is this the real life?',
        );

        $result = $this->service->updateSongs([$song1->id, $song2->id], $data);

        self::assertEquals(2, $result->updatedSongs->count());

        /** @var Song $updatedSong */
        foreach ($result->updatedSongs as $updatedSong) {
            self::assertEquals(2, $updatedSong->disc);
            self::assertEquals(5, $updatedSong->track);
            self::assertSame(2023, $updatedSong->year);
            self::assertEquals('Is this the real life?', $updatedSong->lyrics);
            self::assertEquals('Pop', $updatedSong->genre);
            self::assertSame('New Album Artist', $updatedSong->album_artist->name);
            self::assertSame('Queen', $updatedSong->artist_name);
        }
    }

    #[Test]
    public function updateMultipleTracksWithoutProvidingTrack(): void
    {
        $song1 = Song::factory()->createOne(['track' => 1, 'disc' => 1]);
        $song2 = Song::factory()->createOne(['track' => 2, 'disc' => 1]);

        $data = SongUpdateData::make(artistName: 'Queen', genre: 'Rock', lyrics: 'Is this the real life?');

        $result = $this->service->updateSongs([$song1->id, $song2->id], $data);

        $updatedSongs = $result->updatedSongs;
        self::assertCount(2, $updatedSongs);

        $updatedSongs->each(static function (Song $song): void {
            self::assertEquals(1, $song->disc);
            self::assertEquals('Is this the real life?', $song->lyrics);
            self::assertEquals('Rock', $song->genre);
            self::assertEquals('Queen', $song->artist_name);
            self::assertEquals('Queen', $song->artist->name);
        });

        self::assertEquals(1, $updatedSongs[0]->track);
        self::assertEquals(2, $updatedSongs[1]->track);
    }

    #[Test]
    public function deleteSongs(): void
    {
        $songs = Song::factory()->createMany(2);

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
    }

    #[Test]
    public function deleteSongsWithTranscodes(): void
    {
        $transcodes = Transcode::factory()->createMany(2);
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
    }

    #[Test]
    public function createOrUpdateFromScan(): void
    {
        Dispatcher::expects('dispatch')->with(ExtractSongFolderStructureJob::class);

        $info = app(FileScanner::class)->scan(test_path('songs/full.mp3'));
        $song = $this->service->createOrUpdateSongFromScan($info, ScanConfiguration::make(owner: create_admin()));

        self::assertArraySubset(
            [
                'title' => 'Amet',
                'track' => 5,
                'disc' => 3,
                'lyrics' => "Foo\rbar",
                'mtime' => filemtime(test_path('songs/full.mp3')),
                'year' => 2015,
                'is_public' => false,
                'artist_name' => 'Koel',
                'album_name' => 'Koel Testing Vol. 1',
                'file_size' => 72_081,
            ],
            $song->getAttributes(),
        );

        self::assertSame(2015, $song->album->year);
    }

    #[Test]
    public function creatingOrUpdatingFromScanSetsAlbumReleaseYearIfApplicable(): void
    {
        Dispatcher::expects('dispatch')->with(ExtractSongFolderStructureJob::class);

        $owner = create_admin();
        $artist = Artist::factory(['name' => 'Koel'])->for($owner)->create();
        $album = Album::factory([
            'name' => 'Koel Testing Vol. 1',
            'year' => null,
        ])->for($owner)->for($artist)->create();

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
        $artist = Artist::factory(['name' => 'Koel'])->for($owner)->create();
        $album = Album::factory([
            'name' => 'Koel Testing Vol. 1',
            'year' => 2018,
        ])->for($owner)->for($artist)->create();

        $info = app(FileScanner::class)->scan(test_path('songs/full.mp3'));
        $song = $this->service->createOrUpdateSongFromScan($info, ScanConfiguration::make(owner: $owner));

        self::assertTrue($song->album->is($album));

        self::assertSame(2018, $album->refresh()->year);
    }

    #[Test]
    public function scanContinuesWhenCoverArtExtractionFails(): void
    {
        Dispatcher::expects('dispatch')->with(ExtractSongFolderStructureJob::class);

        $albumService = Mockery::mock(AlbumService::class);
        $albumService->shouldReceive('storeAlbumCover')->andThrow(new RuntimeException('DecoderException'));
        $albumService->shouldReceive('trySetAlbumCoverFromDirectory');
        $this->app->instance(AlbumService::class, $albumService);

        $this->service = app(SongService::class);

        $info = app(FileScanner::class)->scan(test_path('songs/full.mp3'));
        $song = $this->service->createOrUpdateSongFromScan($info, ScanConfiguration::make(owner: create_admin()));

        self::assertSame('Amet', $song->title);
    }

    #[Test]
    public function updateCompilationSongPreservesAlbumArtist(): void
    {
        $user = create_user();
        $this->actingAs($user);

        $variousArtist = Artist::getOrCreate($user, Artist::VARIOUS_NAME);
        $songArtist = Artist::getOrCreate($user, 'Dark Tranquillity');
        $album = Album::getOrCreate($variousArtist, 'A Tribute To The Four Horsemen');

        $song = Song::factory()->for($user, 'owner')->createOne([
            'artist_id' => $songArtist->id,
            'artist_name' => $songArtist->name,
            'album_id' => $album->id,
            'album_name' => $album->name,
            'track' => 1,
        ]);

        // Edit the song without providing album artist (as the UI does)
        $data = SongUpdateData::make(track: 5);
        $result = $this->service->updateSongs([$song->id], $data);

        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        // The album artist should still be "Various Artists", not "Dark Tranquillity"
        self::assertSame(Artist::VARIOUS_NAME, $updatedSong->album_artist->name);
        self::assertTrue($updatedSong->album->is($album));
        self::assertSame(5, $updatedSong->track);
    }

    #[Test]
    public function updateCompilationSongArtistPreservesAlbumArtist(): void
    {
        $user = create_user();
        $this->actingAs($user);

        $variousArtist = Artist::getOrCreate($user, Artist::VARIOUS_NAME);
        $songArtist = Artist::getOrCreate($user, 'Dark Tranquillity');
        $album = Album::getOrCreate($variousArtist, 'A Tribute To The Four Horsemen');

        $song = Song::factory()->for($user, 'owner')->createOne([
            'artist_id' => $songArtist->id,
            'artist_name' => $songArtist->name,
            'album_id' => $album->id,
            'album_name' => $album->name,
        ]);

        // Change the song artist — compilation album artist should be preserved
        $data = SongUpdateData::make(artistName: 'Sonata Arctica');
        $result = $this->service->updateSongs([$song->id], $data);

        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        self::assertSame('Sonata Arctica', $updatedSong->artist->name);
        self::assertSame(Artist::VARIOUS_NAME, $updatedSong->album_artist->name);
        self::assertTrue($updatedSong->album->is($album));
    }

    #[Test]
    public function updateMultipleCompilationSongsPreservesAlbumArtist(): void
    {
        $user = create_user();
        $this->actingAs($user);

        $variousArtist = Artist::getOrCreate($user, Artist::VARIOUS_NAME);
        $album = Album::getOrCreate($variousArtist, 'A Tribute To The Four Horsemen');

        $song1 = Song::factory()->for($user, 'owner')->createOne([
            'artist_id' => Artist::getOrCreate($user, 'Dark Tranquillity')->id,
            'artist_name' => 'Dark Tranquillity',
            'album_id' => $album->id,
            'album_name' => $album->name,
        ]);

        $song2 = Song::factory()->for($user, 'owner')->createOne([
            'artist_id' => Artist::getOrCreate($user, 'Sonata Arctica')->id,
            'artist_name' => 'Sonata Arctica',
            'album_id' => $album->id,
            'album_name' => $album->name,
        ]);

        // Batch update genre without providing album artist
        $data = SongUpdateData::make(genre: 'Metal');
        $result = $this->service->updateSongs([$song1->id, $song2->id], $data);

        foreach ($result->updatedSongs as $updatedSong) {
            self::assertSame(Artist::VARIOUS_NAME, $updatedSong->album_artist->name);
            self::assertTrue($updatedSong->album->is($album));
            self::assertSame('Metal', $updatedSong->genre);
        }
    }

    #[Test]
    public function updateCompilationSongWithExplicitAlbumArtistOverrides(): void
    {
        $user = create_user();
        $this->actingAs($user);

        $variousArtist = Artist::getOrCreate($user, Artist::VARIOUS_NAME);
        $songArtist = Artist::getOrCreate($user, 'Dark Tranquillity');
        $album = Album::getOrCreate($variousArtist, 'A Tribute To The Four Horsemen');

        $song = Song::factory()->for($user, 'owner')->createOne([
            'artist_id' => $songArtist->id,
            'artist_name' => $songArtist->name,
            'album_id' => $album->id,
            'album_name' => $album->name,
        ]);

        // Explicitly change the album artist — should override
        $data = SongUpdateData::make(albumArtistName: 'Dark Tranquillity');
        $result = $this->service->updateSongs([$song->id], $data);

        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        self::assertSame('Dark Tranquillity', $updatedSong->album_artist->name);
        self::assertFalse($updatedSong->album->is($album));
    }

    #[Test]
    public function updateNonCompilationSongArtistAlsoUpdatesAlbumArtist(): void
    {
        $song = Song::factory()->createOne();
        $originalAlbumArtist = $song->album_artist;

        // Verify non-compilation: album artist === song artist
        self::assertTrue($originalAlbumArtist->is($song->artist));

        // Change artist without specifying album artist
        $data = SongUpdateData::make(artistName: 'New Artist');
        $result = $this->service->updateSongs([$song->id], $data);

        /** @var Song $updatedSong */
        $updatedSong = $result->updatedSongs->first();

        // For non-compilation songs, album artist should follow the song artist
        self::assertSame('New Artist', $updatedSong->artist->name);
        self::assertSame('New Artist', $updatedSong->album_artist->name);
    }
}
