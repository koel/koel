<?php

namespace Tests\Integration\Services;

use App\Events\LibraryChanged;
use App\Events\MediaScanCompleted;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Services\FileScanner;
use App\Services\MediaScanner;
use App\Values\ScanConfiguration;
use App\Values\WatchRecord\InotifyWatchRecord;
use getID3;
use Illuminate\Support\Arr;
use Mockery;
use Tests\TestCase;

use function Tests\create_admin;

class MediaScannerTest extends TestCase
{
    private MediaScanner $scanner;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', realpath($this->mediaPath));
        $this->scanner = app(MediaScanner::class);
    }

    private function path($subPath): string
    {
        return realpath($this->mediaPath . $subPath);
    }

    public function testScan(): void
    {
        $this->expectsEvents(MediaScanCompleted::class);

        $owner = create_admin();
        $this->scanner->scan(ScanConfiguration::make(owner: $owner));

        // Standard mp3 files under root path should be recognized
        self::assertDatabaseHas(Song::class, [
            'path' => $this->path('/full.mp3'),
            'track' => 5,
            'owner_id' => $owner->id,
        ]);

        // Ogg files and audio files in subdirectories should be recognized
        self::assertDatabaseHas(Song::class, [
            'path' => $this->path('/subdir/back-in-black.ogg'),
            'owner_id' => $owner->id,
        ]);

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

    public function testModifiedFileIsRescanned(): void
    {
        $this->expectsEvents(MediaScanCompleted::class);

        $config = ScanConfiguration::make(owner: create_admin());
        $this->scanner->scan($config);

        /** @var Song $song */
        $song = Song::query()->first();

        touch($song->path, $time = time() + 1000);
        $this->scanner->scan($config);

        self::assertSame($time, $song->refresh()->mtime);
    }

    public function testRescanWithoutForceDoesNotResetData(): void
    {
        $this->expectsEvents(MediaScanCompleted::class);

        $config = ScanConfiguration::make(owner: create_admin());

        $this->scanner->scan($config);

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->scanner->scan($config);

        $song->refresh();
        self::assertSame("It's John Cena!", $song->title);
        self::assertSame('Booom Wroooom', $song->lyrics);
    }

    public function testForceScanResetsData(): void
    {
        $this->expectsEvents(MediaScanCompleted::class);

        $owner = create_admin();
        $this->scanner->scan(ScanConfiguration::make(owner: $owner));

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->scanner->scan(ScanConfiguration::make(owner: create_admin(), force: true));

        $song->refresh();

        self::assertNotSame("It's John Cena!", $song->title);
        self::assertNotSame('Booom Wroooom', $song->lyrics);
        // make sure the user is not changed
        self::assertSame($owner->id, $song->owner_id);
    }

    public function testScanWithIgnoredTags(): void
    {
        $this->expectsEvents(MediaScanCompleted::class);

        $owner = create_admin();
        $this->scanner->scan(ScanConfiguration::make(owner: $owner));

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->scanner->scan(ScanConfiguration::make(owner: $owner, ignores: ['title'], force: true));

        $song->refresh();

        self::assertSame("It's John Cena!", $song->title);
        self::assertNotSame('Booom Wroooom', $song->lyrics);
    }

    public function testScanAllTagsForNewFilesRegardlessOfIgnoredOption(): void
    {
        $this->expectsEvents(MediaScanCompleted::class);

        $owner = create_admin();
        $this->scanner->scan(ScanConfiguration::make(owner: $owner));

        /** @var Song $song */
        $song = Song::query()->first();

        $song->delete();

        $this->scanner->scan(ScanConfiguration::make(
            owner: $owner,
            ignores: ['title', 'disc', 'track'],
            force: true
        ));

        // Song should be added back with all info
        self::assertEquals(
            Arr::except(Song::query()->where('path', $song->path)->first()->toArray(), ['id', 'created_at']),
            Arr::except($song->toArray(), ['id', 'created_at'])
        );
    }

    public function testScanAddedSongViaWatch(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $path = $this->path('/blank.mp3');

        $this->scanner->scanWatchRecord(
            new InotifyWatchRecord("CLOSE_WRITE,CLOSE $path"),
            ScanConfiguration::make(owner: create_admin())
        );

        self::assertDatabaseHas(Song::class, ['path' => $path]);
    }

    public function testScanDeletedSongViaWatch(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        /** @var Song $song */
        $song = Song::factory()->create();

        $this->scanner->scanWatchRecord(
            new InotifyWatchRecord("DELETE $song->path"),
            ScanConfiguration::make(owner: create_admin())
        );

        self::assertModelMissing($song);
    }

    public function testScanDeletedDirectoryViaWatch(): void
    {
        $this->expectsEvents(LibraryChanged::class, MediaScanCompleted::class);

        $config = ScanConfiguration::make(owner: create_admin());

        $this->scanner->scan($config);
        $this->scanner->scanWatchRecord(new InotifyWatchRecord("MOVED_FROM,ISDIR $this->mediaPath/subdir"), $config);

        self::assertDatabaseMissing(Song::class, ['path' => $this->path('/subdir/sic.mp3')]);
        self::assertDatabaseMissing(Song::class, ['path' => $this->path('/subdir/no-name.mp3')]);
        self::assertDatabaseMissing(Song::class, ['path' => $this->path('/subdir/back-in-black.mp3')]);
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

        /** @var FileScanner $fileScanner */
        $fileScanner = app(FileScanner::class);
        $info = $fileScanner->setFile($path)->getScanInformation();

        self::assertSame('佐倉綾音 Unknown', $info->artistName);
        self::assertSame('小岩井こ Random', $info->albumName);
        self::assertSame('水谷広実', $info->title);
    }

    public function testOptionallyIgnoreHiddenFiles(): void
    {
        $config = ScanConfiguration::make(owner: create_admin());

        config(['koel.ignore_dot_files' => false]);
        $this->scanner->scan($config);
        self::assertDatabaseHas(Album::class, ['name' => 'Hidden Album']);

        config(['koel.ignore_dot_files' => true]);
        $this->scanner->scan($config);
        self::assertDatabaseMissing(Album::class, ['name' => 'Hidden Album']);
    }
}
