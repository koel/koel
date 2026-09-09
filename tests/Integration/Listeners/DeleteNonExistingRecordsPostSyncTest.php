<?php

namespace Tests\Integration\Listeners;

use App\Enums\SongStorageType;
use App\Events\MediaScanCompleted;
use App\Listeners\DeleteNonExistingRecordsPostScan;
use App\Models\Album;
use App\Models\Song;
use App\Values\Scanning\ScanResult;
use App\Values\Scanning\ScanResultCollection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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
                $this->listener->handle(new MediaScanCompleted(ScanResultCollection::create()));

                $this->assertModelExists($song);
            });
    }

    #[Test]
    public function handleDoesNotDeleteEpisodes(): void
    {
        $episode = Song::factory()->asEpisode()->createOne();
        $this->listener->handle(new MediaScanCompleted(ScanResultCollection::create()));
        $this->assertModelExists($episode);
    }

    #[Test]
    public function handle(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory()->createMany(4);

        self::assertCount(4, Song::all());

        $syncResult = ScanResultCollection::create();
        $syncResult->add(ScanResult::success($songs[0]->path));
        $syncResult->add(ScanResult::skipped($songs[3]->path));

        $this->listener->handle(new MediaScanCompleted($syncResult));

        $this->assertModelExists($songs[0]);
        $this->assertModelExists($songs[3]);
        $this->assertModelMissing($songs[1]);
        $this->assertModelMissing($songs[2]);
    }

    #[Test]
    public function prunesAlbumsAndArtistsLeftEmptyByTheScan(): void
    {
        $album = Album::factory()->createOne();
        $artist = $album->artist;
        $song = Song::factory()->for($album)->for($artist)->createOne();

        $unaffectedAlbum = Album::factory()->createOne();
        Song::factory()->for($unaffectedAlbum)->for($unaffectedAlbum->artist)->createOne();

        $syncResult = ScanResultCollection::create();
        $syncResult->add(ScanResult::success($unaffectedAlbum->songs->first()->path));

        $this->listener->handle(new MediaScanCompleted($syncResult));

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

        $syncResult = ScanResultCollection::create();

        $this->listener->handle(new MediaScanCompleted($syncResult));

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
        $this->listener->handle(new MediaScanCompleted(ScanResultCollection::create()));

        $songs->each($this->assertModelExists(...));

        // The warning is half of what this guard is for. Silently keeping the rows would leave an
        // operator with a library that stopped updating and nothing saying why.
        Log::shouldHaveReceived('warning') // @phpstan-ignore-line
            ->once()
            ->withArgs(static fn (string $message) => str_contains($message, 'Refusing to delete'));
    }

    #[Test]
    public function stillDeletesVanishedFilesWhenTheScanFoundSomething(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory()->createMany(2);

        // A non-empty result is unambiguous, so the guard must not interfere with it.
        $syncResult = ScanResultCollection::create();
        $syncResult->add(ScanResult::success($songs[0]->path));

        $this->listener->handle(new MediaScanCompleted($syncResult));

        $this->assertModelExists($songs[0]);
        $this->assertModelMissing($songs[1]);
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

        $syncResult = ScanResultCollection::create();
        $syncResult->add(ScanResult::success($kept->path));

        $this->listener->handle(new MediaScanCompleted($syncResult));

        self::assertModelMissing($orphan);
        $engine->shouldHaveReceived('delete')->atLeast()->once(); // @phpstan-ignore-line
    }
}
