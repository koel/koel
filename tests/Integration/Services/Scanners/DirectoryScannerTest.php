<?php

namespace Tests\Integration\Services\Scanners;

use App\Events\MediaScanCompleted;
use App\Events\SongFolderStructureExtractionRequested;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Services\Scanners\DirectoryScanner;
use App\Services\Scanners\FileScanner;
use App\Values\Scanning\ScanConfiguration;
use getID3;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class DirectoryScannerTest extends TestCase
{
    private DirectoryScanner $scanner;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', realpath($this->mediaPath));
        $this->scanner = app(DirectoryScanner::class);
    }

    private function path(string $subPath): string
    {
        return realpath($this->mediaPath . $subPath);
    }

    #[Test]
    public function scan(): void
    {
        Event::fake([MediaScanCompleted::class, SongFolderStructureExtractionRequested::class]);

        $owner = create_admin();
        $this->scanner->scan($this->mediaPath, ScanConfiguration::make(owner: $owner));

        // Standard mp3 files under the root path should be recognized
        $this->assertDatabaseHas(Song::class, [
            'path' => $this->path('/full.mp3'),
            'track' => 5,
            'owner_id' => $owner->id,
        ]);

        // Ogg files and audio files in subdirectories should be recognized
        $this->assertDatabaseHas(Song::class, [
            'path' => $this->path('/subdir/back-in-black.ogg'),
            'owner_id' => $owner->id,
        ]);

        // GitHub issue #380. folder.png should be copied and used as the cover for files
        // under subdir/
        /** @var Song $song */
        $song = Song::query()->where('path', $this->path('/subdir/back-in-black.ogg'))->first();
        self::assertNotEmpty($song->album->cover);

        // File search shouldn't be case-sensitive.
        $this->assertDatabaseHas(Song::class, ['path' => $this->path('/subdir/no-name.mp3')]);

        // Non-audio files shouldn't be recognized
        $this->assertDatabaseMissing(Song::class, ['path' => $this->path('/rubbish.log')]);

        // Broken/corrupted audio files shouldn't be recognized
        $this->assertDatabaseMissing(Song::class, ['path' => $this->path('/fake.mp3')]);

        // Artists should be created
        $this->assertDatabaseHas(Artist::class, [
            'name' => 'Cuckoo',
            'user_id' => $owner->id,
        ]);

        $this->assertDatabaseHas(Artist::class, [
            'name' => 'Koel',
            'user_id' => $owner->id,
        ]);

        // Albums should be created
        $this->assertDatabaseHas(Album::class, ['name' => 'Koel Testing Vol. 1']);

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

        // An event should be dispatched to indicate the scan is completed
        Event::assertDispatched(MediaScanCompleted::class);

        // Events should be dispatched to indicate the folder structure extraction is requested for several songs
        Event::assertDispatched(SongFolderStructureExtractionRequested::class);
    }

    #[Test]
    public function modifiedFileIsRescanned(): void
    {
        Event::fake([MediaScanCompleted::class, SongFolderStructureExtractionRequested::class]);

        $config = ScanConfiguration::make(owner: create_admin());
        $this->scanner->scan($this->mediaPath, $config);

        /** @var Song $song */
        $song = Song::query()->latest()->first();

        $time = $song->mtime + 1000;
        touch($song->path, $time);
        $this->scanner->scan($this->mediaPath, $config);

        self::assertSame($time, $song->refresh()->mtime);
    }

    #[Test]
    public function rescanWithoutForceDoesNotResetData(): void
    {
        Event::fake([MediaScanCompleted::class, SongFolderStructureExtractionRequested::class]);

        $config = ScanConfiguration::make(owner: create_admin());

        $this->scanner->scan($this->mediaPath, $config);

        /** @var Song $song */
        $song = Song::query()->latest()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->scanner->scan($this->mediaPath, $config);

        $song->refresh();
        self::assertSame("It's John Cena!", $song->title);
        self::assertSame('Booom Wroooom', $song->lyrics);
    }

    #[Test]
    public function forceScanResetsData(): void
    {
        Event::fake([MediaScanCompleted::class, SongFolderStructureExtractionRequested::class]);

        $owner = create_admin();
        $this->scanner->scan($this->mediaPath, ScanConfiguration::make(owner: $owner));

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->scanner->scan($this->mediaPath, ScanConfiguration::make(owner: create_admin(), force: true));

        $song->refresh();

        self::assertNotSame("It's John Cena!", $song->title);
        self::assertNotSame('Booom Wroooom', $song->lyrics);

        // make sure the user is not changed
        self::assertSame($owner->id, $song->owner_id);
        self::assertSame($owner->id, $song->artist->user_id);
        self::assertSame($owner->id, $song->album->user_id);
    }

    #[Test]
    public function ignoredTagsAreIgnoredEvenWithForceRescanning(): void
    {
        Event::fake([MediaScanCompleted::class, SongFolderStructureExtractionRequested::class]);

        $owner = create_admin();
        $this->scanner->scan($this->mediaPath, ScanConfiguration::make(owner: $owner));

        /** @var Song $song */
        $song = Song::query()->first();

        $song->update([
            'title' => "It's John Cena!",
            'lyrics' => 'Booom Wroooom',
        ]);

        $this->scanner->scan($this->mediaPath, ScanConfiguration::make(owner: $owner, ignores: ['title'], force: true));

        $song->refresh();

        self::assertSame("It's John Cena!", $song->title);
        self::assertNotSame('Booom Wroooom', $song->lyrics);
    }

    #[Test]
    public function scanAllTagsForNewFilesRegardlessOfIgnoredOption(): void
    {
        Event::fake([MediaScanCompleted::class, SongFolderStructureExtractionRequested::class]);

        $owner = create_admin();
        $this->scanner->scan($this->mediaPath, ScanConfiguration::make(owner: $owner));

        /** @var Song $song */
        $song = Song::query()->first();

        $song->delete();

        $this->scanner->scan(
            $this->mediaPath,
            ScanConfiguration::make(
                owner: $owner,
                ignores: ['title', 'disc', 'track'],
                force: true
            )
        );

        // Song should be added back with all info
        self::assertEquals(
            Arr::except(Song::query()->where('path', $song->path)->first()->toArray(), ['id', 'created_at']),
            Arr::except($song->toArray(), ['id', 'created_at'])
        );
    }

    #[Test]
    public function htmlEntities(): void
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
        $info = $fileScanner->scan($path);

        self::assertSame('佐倉綾音 Unknown', $info->artistName);
        self::assertSame('小岩井こ Random', $info->albumName);
        self::assertSame('水谷広実', $info->title);
    }

    #[Test]
    public function optionallyIgnoreHiddenFiles(): void
    {
        $path = Setting::get('media_path');

        $config = ScanConfiguration::make(owner: create_admin());

        config(['koel.ignore_dot_files' => false]);
        $this->scanner->scan($path, $config);
        $this->assertDatabaseHas(Album::class, ['name' => 'Hidden Album']);

        config(['koel.ignore_dot_files' => true]);
        $this->scanner->scan($path, $config);
        $this->assertDatabaseMissing(Album::class, ['name' => 'Hidden Album']);
    }
}
