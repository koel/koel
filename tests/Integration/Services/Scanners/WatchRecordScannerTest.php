<?php

namespace Tests\Integration\Services\Scanners;

use App\Events\MediaScanCompleted;
use App\Models\Setting;
use App\Models\Song;
use App\Services\Scanners\DirectoryScanner;
use App\Services\Scanners\WatchRecordScanner;
use App\Values\Scanning\ScanConfiguration;
use App\Values\WatchRecord\InotifyWatchRecord;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class WatchRecordScannerTest extends TestCase
{
    private WatchRecordScanner $scanner;

    public function setUp(): void
    {
        parent::setUp();

        Setting::set('media_path', realpath($this->mediaPath));
        $this->scanner = app(WatchRecordScanner::class);
    }

    private function path(string $subPath): string
    {
        return realpath($this->mediaPath . $subPath);
    }

    #[Test]
    public function scanAddedSongViaWatch(): void
    {
        $path = $this->path('/blank.mp3');

        $this->scanner->scan(
            new InotifyWatchRecord("CLOSE_WRITE,CLOSE $path"),
            ScanConfiguration::make(owner: create_admin())
        );

        $this->assertDatabaseHas(Song::class, ['path' => $path]);
    }

    #[Test]
    public function scanDeletedSongViaWatch(): void
    {
        /** @var Song $song */
        $song = Song::factory()->create();

        $this->scanner->scan(
            new InotifyWatchRecord("DELETE $song->path"),
            ScanConfiguration::make(owner: create_admin())
        );

        $this->assertModelMissing($song);
    }

    #[Test]
    public function scanDeletedDirectoryViaWatch(): void
    {
        Event::fake(MediaScanCompleted::class);

        $config = ScanConfiguration::make(owner: create_admin());

        app(DirectoryScanner::class)->scan($this->mediaPath, $config);
        $this->scanner->scan(new InotifyWatchRecord("MOVED_FROM,ISDIR $this->mediaPath/subdir"), $config);

        $this->assertDatabaseMissing(Song::class, ['path' => $this->path('/subdir/sic.mp3')]);
        $this->assertDatabaseMissing(Song::class, ['path' => $this->path('/subdir/no-name.mp3')]);
        $this->assertDatabaseMissing(Song::class, ['path' => $this->path('/subdir/back-in-black.mp3')]);
    }
}
