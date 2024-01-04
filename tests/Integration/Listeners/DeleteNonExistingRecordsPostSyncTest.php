<?php

namespace Tests\Integration\Listeners;

use App\Events\MediaScanCompleted;
use App\Listeners\DeleteNonExistingRecordsPostSync;
use App\Models\Song;
use App\Values\ScanResult;
use App\Values\ScanResultCollection;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class DeleteNonExistingRecordsPostSyncTest extends TestCase
{
    private DeleteNonExistingRecordsPostSync $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->listener = app(DeleteNonExistingRecordsPostSync::class);
    }

    public function testHandleDoesNotDeleteS3Entries(): void
    {
        $song = Song::factory()->create(['path' => 's3://do-not/delete-me.mp3']);
        $this->listener->handle(new MediaScanCompleted(ScanResultCollection::create()));

        self::assertModelExists($song);
    }

    public function testHandle(): void
    {
        /** @var Collection|array<Song> $songs */
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
