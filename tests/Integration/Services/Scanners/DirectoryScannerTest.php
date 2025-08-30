<?php

namespace Tests\Integration\Services\Scanners;

use App\Events\MediaScanCompleted;
use App\Facades\Dispatcher;
use App\Jobs\ExtractSongFolderStructureJob;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Setting;
use App\Models\Song;
use App\Services\Scanners\DirectoryScanner;
use App\Values\Scanning\ScanConfiguration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
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
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

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
    }

    #[Test]
    public function modifiedFileIsRescanned(): void
    {
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

        $config = ScanConfiguration::make(owner: create_admin());
        $this->scanner->scan($this->mediaPath, $config);

        /** @var Song $song */
        $song = Song::query()->latest()->first();
        $correctHash = $song->hash;
        $song->update(['hash' => 'fake-old-hash']);

        $this->scanner->scan($this->mediaPath, $config);

        self::assertSame($correctHash, $song->refresh()->hash);
    }

    #[Test]
    public function rescanWithoutForceDoesNotResetData(): void
    {
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

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
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

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
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

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
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

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

        $ignores = [
            'id',
            'created_at',
            'updated_at',
        ];

        // Song should be added back with all info
        self::assertEquals(
            Arr::except(Song::query()->where('path', $song->path)->first()->withoutRelations()->toArray(), $ignores),
            Arr::except($song->withoutRelations()->toArray(), $ignores)
        );
    }

    #[Test]
    public function hiddenFilesAndFoldersAreIgnoredIfConfiguredSo(): void
    {
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

        $config = ScanConfiguration::make(owner: create_admin());

        config(['koel.ignore_dot_files' => true]);
        $this->scanner->scan($this->mediaPath, $config);
        $this->assertDatabaseMissing(Album::class, ['name' => 'Hidden Album']);
    }

    #[Test]
    public function hiddenFilesAndFoldersAreScannedIfConfiguredSo(): void
    {
        Event::fake([MediaScanCompleted::class]);
        Dispatcher::shouldReceive('dispatch')->with(ExtractSongFolderStructureJob::class)->atLeast();

        $config = ScanConfiguration::make(owner: create_admin());

        config(['koel.ignore_dot_files' => false]);
        $this->scanner->scan($this->mediaPath, $config);
        $this->assertDatabaseHas(Album::class, ['name' => 'Hidden Album']);
    }
}
