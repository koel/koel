<?php

namespace Tests\Integration\Listeners;

use App\Enums\SongStorageType;
use App\Events\MediaScanCompleted;
use App\Listeners\DeleteNonExistingRecordsPostScan;
use App\Models\Album;
use App\Models\Song;
use App\Services\LibrarySummaryService;
use App\Services\Scanners\ScannedFileService;
use App\Values\Scanning\ScanResult;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DeleteNonExistingRecordsPostSyncTest extends TestCase
{
    private DeleteNonExistingRecordsPostScan $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = app(DeleteNonExistingRecordsPostScan::class);
    }

    #[Test]
    public function handleDoesNotDeleteCloudEntries(): void
    {
        collect(SongStorageType::cases())
            ->filter(static fn ($type) => $type !== SongStorageType::LOCAL)
            ->each(function ($type): void {
                $song = Song::factory()->createOne(['storage' => $type]);
                $this->listener->handle(self::recordScan(ScanResult::success('/media/other.mp3')));

                $this->assertModelExists($song);
            });
    }

    #[Test]
    public function handleDoesNotDeleteEpisodes(): void
    {
        $episode = Song::factory()->asEpisode()->createOne();
        $this->listener->handle(self::recordScan(ScanResult::success('/media/other.mp3')));
        $this->assertModelExists($episode);
    }

    #[Test]
    public function handle(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory()->createMany(4);

        self::assertCount(4, Song::all());

        $this->listener->handle(self::recordScan(
            ScanResult::success($songs[0]->path),
            ScanResult::skipped($songs[3]->path),
        ));

        $this->assertModelExists($songs[0]);
        $this->assertModelExists($songs[3]);
        $this->assertModelMissing($songs[1]);
        $this->assertModelMissing($songs[2]);
    }

    #[Test]
    public function removingSongsRefreshesTheSongCount(): void
    {
        $user = create_user();
        $songs = Song::factory()->createMany(2);
        $librarySummaryService = app(LibrarySummaryService::class);

        self::assertSame(2, $librarySummaryService->getSongCount($user));

        $this->listener->handle(self::recordScan(ScanResult::success($songs[0]->path)));

        self::assertSame(1, $librarySummaryService->getSongCount($user));
    }

    #[Test]
    public function prunesAlbumsAndArtistsLeftEmptyByTheScan(): void
    {
        $album = Album::factory()->createOne();
        $artist = $album->artist;
        $song = Song::factory()->for($album)->for($artist)->createOne();

        $unaffectedAlbum = Album::factory()->createOne();
        Song::factory()->for($unaffectedAlbum)->for($unaffectedAlbum->artist)->createOne();

        $this->listener->handle(self::recordScan(ScanResult::success($unaffectedAlbum->songs->first()->path)));

        self::assertModelMissing($song);
        self::assertModelMissing($album);
        self::assertModelMissing($artist);
        self::assertModelExists($unaffectedAlbum);
    }

    #[Test]
    public function prunesAlbumsAndArtistsOrphanedByPriorSongDeletions(): void
    {
        $album = Album::factory()->createOne();
        $artist = $album->artist;

        $this->listener->handle(self::recordScan());

        self::assertModelMissing($album);
        self::assertModelMissing($artist);
    }

    #[Test]
    public function refusesToDeleteEverythingWhenAScanReturnsNothing(): void
    {
        Log::spy();

        $songs = Song::factory()->createMany(3);

        // What a mount point whose backing filesystem went away produces: a scan that completes
        // successfully over a readable directory and reports no valid files at all.
        $this->listener->handle(self::recordScan());

        $songs->each($this->assertModelExists(...));

        // The warning is half of what this guard is for. Silently keeping the rows would leave an
        // operator with a library that stopped updating and nothing saying why.
        Log::shouldHaveReceived('warning') // @phpstan-ignore-line
            ->once()
            ->withArgs(static fn (string $message) => str_contains($message, 'Refusing to delete'));
    }

    #[Test]
    public function logsHowManySongsWereRemoved(): void
    {
        Log::spy();

        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory()->createMany(3);

        $this->listener->handle(self::recordScan(ScanResult::success($songs[0]->path)));

        Log::shouldHaveReceived('info') // @phpstan-ignore-line
            ->once()
            ->withArgs(static fn (string $message) => str_contains($message, 'Removed 2 song(s)'));
    }

    #[Test]
    public function logsNothingWhenNoSongWasRemoved(): void
    {
        Log::spy();

        $song = Song::factory()->createOne();

        $this->listener->handle(self::recordScan(ScanResult::success($song->path)));

        Log::shouldNotHaveReceived('info'); // @phpstan-ignore-line
    }

    #[Test]
    public function flushesOrphanedSongsFromSearchIndex(): void
    {
        $engine = Mockery::spy(Engine::class);
        $manager = Mockery::mock(EngineManager::class);
        $manager->shouldReceive('engine')->andReturn($engine);
        $this->app->instance(EngineManager::class, $manager);

        $kept = Song::factory()->createOne();
        $orphan = Song::factory()->createOne();

        $this->listener->handle(self::recordScan(ScanResult::success($kept->path)));

        self::assertModelMissing($orphan);
        $engine->shouldHaveReceived('delete')->atLeast()->once(); // @phpstan-ignore-line
    }

    #[Test]
    public function deletesNothingOnceTheScannedFilesAreCleared(): void
    {
        $songs = Song::factory()->createMany(2);
        $event = self::recordScan(ScanResult::success($songs[0]->path));

        app(ScannedFileService::class)->deleteScannedFiles($event->summary->scanId);
        $this->listener->handle($event);

        $songs->each($this->assertModelExists(...));
    }

    private static function recordScan(ScanResult ...$results): MediaScanCompleted
    {
        $recorder = app(ScannedFileService::class)->startScan();

        foreach ($results as $result) {
            $recorder->record($result);
        }

        return new MediaScanCompleted($recorder->finish());
    }
}
