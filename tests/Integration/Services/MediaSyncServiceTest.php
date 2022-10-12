<?php

namespace Tests\Integration\Services;

use App\Events\LibraryChanged;
use App\Events\MediaSyncCompleted;
use App\Libraries\WatchRecord\InotifyWatchRecord;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Services\FileSynchronizer;
use App\Services\MediaSyncService;
use getID3;
use Illuminate\Support\Arr;
use Mockery;
use Tests\Feature\TestCase;

class MediaSyncServiceTest extends TestCase
{
    private MediaSyncService $mediaService;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', realpath($this->mediaPath));
        $this->mediaService = app(MediaSyncService::class);
    }

    private function path($subPath): string
    {
        return realpath($this->mediaPath . $subPath);
    }

    public function testSync(): void
    {
        $this->expectsEvents(MediaSyncCompleted::class);

        $this->mediaService->sync();

        // Standard mp3 files under root path should be recognized
        self::assertDatabaseHas(Song::class, [
            'path' => $this->path('/full.mp3'),
            'track' => 5,
        ]);

        // Ogg files and audio files in subdirectories should be recognized
        self::assertDatabaseHas(Song::class, ['path' => $this->path('/subdir/back-in-black.ogg')]);

        // GitHub issue #380. folder.png should be copied and used as the cover for files
        // under subdir/
        /** @var Song $song */
        $song = Song::query()->where('path', $this->path('/subdir/back-in-black.ogg'))->first();
        self::assertNotEmpty($song->album->cover);

        // File search shouldn't be case-sensitive.
        self::assertDatabaseHas(Song::class, ['path' => $this->path('/subdir/no-name.mp3')]);

        // Non-audio files shouldn't be recognized
        self::assertDatabaseMissing(Song::class, ['path' => $this->path('/rubbish.log')]);

        // Broken/corrupted audio files shouldn't be recognized
        self::assertDatabaseMissing(Song::class, ['path' => $this->path('/fake.mp3')]);

        // Artists should be created
        self::assertDatabaseHas(Artist::class, ['name' => 'Cuckoo']);
        self::assertDatabaseHas(Artist::class, ['name' => 'Koel']);

        // Albums should be created
        self::assertDatabaseHas(Album::class, ['name' => 'Koel Testing Vol. 1']);

        // Albums and artists should be correctly linked
        /** @var Album $album */
        $album = Album::query()->where('name', 'Koel Testing Vol. 1')->first();
        self::assertSame('Koel', $album->artist->name);

        // Compilation albums, artists and songs must be recognized
        /** @var Song $song */
        $song = Song::query()->where('title', 'This song belongs to a compilation')->first();
        self::assertFalse($song->album_artist->is($song->artist));
        self::assertSame('Koel', $song->album_artist->name);
        self::assertSame('Cuckoo', $song->artist->name);
    }

    public function testModifiedFileIsResynced(): void
    {
        $this->mediaService->sync();

        /** @var Song $song */
        $song = Song::query()->first();

        touch($song->path, $time = time() + 1000);
        $this->mediaService->sync();

        self::assertSame($time, $song->refresh()->mtime);
    }

    public function testResyncWithoutForceDoesNotResetData(): void
    {
        $this->expectsEvents(MediaSyncCompleted::class);

        $this->mediaService->sync();

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->mediaService->sync();

        $song->refresh();
        self::assertSame("It's John Cena!", $song->title);
        self::assertSame('Booom Wroooom', $song->lyrics);
    }

    public function testForceSyncResetsData(): void
    {
        $this->expectsEvents(MediaSyncCompleted::class);

        $this->mediaService->sync();

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->mediaService->sync(force: true);

        $song->refresh();

        self::assertNotSame("It's John Cena!", $song->title);
        self::assertNotSame('Booom Wroooom', $song->lyrics);
    }

    public function testSyncWithIgnoredTags(): void
    {
        $this->expectsEvents(MediaSyncCompleted::class);

        $this->mediaService->sync();

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->mediaService->sync(ignores: ['title'], force: true);

        $song->refresh();

        self::assertSame("It's John Cena!", $song->title);
        self::assertNotSame('Booom Wroooom', $song->lyrics);
    }

    public function testSyncAllTagsForNewFilesRegardlessOfIgnoredOption(): void
    {
        $this->mediaService->sync();

        /** @var Song $song */
        $song = Song::query()->first();

        $song->delete();

        $this->mediaService->sync(ignores: ['title', 'disc', 'track'], force: true);

        // Song should be added back with all info
        self::assertEquals(
            Arr::except(Song::query()->where('path', $song->path)->first()->toArray(), ['id', 'created_at']),
            Arr::except($song->toArray(), ['id', 'created_at'])
        );
    }

    public function testSyncAddedSongViaWatch(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $path = $this->path('/blank.mp3');
        $this->mediaService->syncByWatchRecord(new InotifyWatchRecord("CLOSE_WRITE,CLOSE $path"));

        self::assertDatabaseHas(Song::class, ['path' => $path]);
    }

    public function testSyncDeletedSongViaWatch(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        static::createSampleMediaSet();

        /** @var Song $song */
        $song = Song::query()->first();

        $this->mediaService->syncByWatchRecord(new InotifyWatchRecord("DELETE $song->path"));

        self::assertModelMissing($song);
    }

    public function testSyncDeletedDirectoryViaWatch(): void
    {
        $this->expectsEvents(LibraryChanged::class, MediaSyncCompleted::class);

        $this->mediaService->sync();

        $this->mediaService->syncByWatchRecord(new InotifyWatchRecord("MOVED_FROM,ISDIR $this->mediaPath/subdir"));

        self::assertDatabaseMissing('songs', ['path' => $this->path('/subdir/sic.mp3')]);
        self::assertDatabaseMissing('songs', ['path' => $this->path('/subdir/no-name.mp3')]);
        self::assertDatabaseMissing('songs', ['path' => $this->path('/subdir/back-in-black.mp3')]);
    }

    public function testHtmlEntities(): void
    {
        $path = $this->path('/songs/blank.mp3');
        $analyzed = [
            'filenamepath' => $path,
            'tags' => [
                'id3v2' => [
                    'title' => ['&#27700;&#35895;&#24195;&#23455;'],
                    'album' => ['&#23567;&#23721;&#20117;&#12371; Random'],
                    'artist' => ['&#20304;&#20489;&#32190;&#38899; Unknown'],
                ],
            ],
            'encoding' => 'UTF-8',
            'playtime_seconds' => 100,
        ];

        $this->swap(
            getID3::class,
            Mockery::mock(getID3::class, [
                'CopyTagsToComments' => $analyzed,
                'analyze' => $analyzed,
            ])
        );

        /** @var FileSynchronizer $fileSynchronizer */
        $fileSynchronizer = app(FileSynchronizer::class);
        $info = $fileSynchronizer->setFile($path)->getFileScanInformation();

        self::assertSame('佐倉綾音 Unknown', $info->artistName);
        self::assertSame('小岩井こ Random', $info->albumName);
        self::assertSame('水谷広実', $info->title);
    }

    public function testOptionallyIgnoreHiddenFiles(): void
    {
        config(['koel.ignore_dot_files' => false]);
        $this->mediaService->sync();
        self::assertDatabaseHas(Album::class, ['name' => 'Hidden Album']);

        config(['koel.ignore_dot_files' => true]);
        $this->mediaService->sync();
        self::assertDatabaseMissing(Album::class, ['name' => 'Hidden Album']);
    }
}
