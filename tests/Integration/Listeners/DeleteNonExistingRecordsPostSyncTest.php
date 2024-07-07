<?php

namespace Tests\Integration\Listeners;

use App\Enums\SongStorageType;
use App\Events\MediaScanCompleted;
use App\Listeners\DeleteNonExistingRecordsPostScan;
use App\Models\Song;
use App\Values\ScanResult;
use App\Values\ScanResultCollection;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class DeleteNonExistingRecordsPostSyncTest extends TestCase
{
    private DeleteNonExistingRecordsPostScan $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = app(DeleteNonExistingRecordsPostScan::class);
    }

    public function testHandleDoesNotDeleteCloudEntries(): void
    {
        collect(SongStorageType::cases())
            ->filter(static fn ($type) => $type !== SongStorageType::LOCAL)
            ->each(function ($type): void {
                $song = Song::factory()->create(['storage' => $type]);
                $this->listener->handle(new MediaScanCompleted(ScanResultCollection::create()));

                self::assertModelExists($song);
            });
    }

    public function testHandleDoesNotDeleteEpisodes(): void
    {
        $episode = Song::factory()->asEpisode()->create();
        $this->listener->handle(new MediaScanCompleted(ScanResultCollection::create()));
        self::assertModelExists($episode);
    }

    public function testHandle(): void
    {
        /** @var Collection|array<array-key, Song> $songs */
        $songs = Song::factory(4)->create();

        self::assertCount(4, Song::all());

        $syncResult = ScanResultCollection::create();
        $syncResult->add(ScanResult::success($songs[0]->path));
        $syncResult->add(ScanResult::skipped($songs[3]->path));

        $this->listener->handle(new MediaScanCompleted($syncResult));

        self::assertModelExists($songs[0]);
        self::assertModelExists($songs[3]);
        self::assertModelMissing($songs[1]);
        self::assertModelMissing($songs[2]);
    }
}
