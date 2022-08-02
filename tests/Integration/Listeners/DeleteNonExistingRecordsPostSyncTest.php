<?php

namespace Tests\Integration\Listeners;

use App\Events\MediaSyncCompleted;
use App\Listeners\DeleteNonExistingRecordsPostSync;
use App\Models\Song;
use App\Values\SyncResult;
use App\Values\SyncResultCollection;
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
        $this->listener->handle(new MediaSyncCompleted(SyncResultCollection::create()));

        self::assertModelExists($song);
    }

    public function testHandle(): void
    {
        /** @var Collection|array<Song> $songs */
        $songs = Song::factory(4)->create();

        self::assertCount(4, Song::all());

        $syncResult = SyncResultCollection::create();
        $syncResult->add(SyncResult::success($songs[0]->path));
        $syncResult->add(SyncResult::skipped($songs[3]->path));

        $this->listener->handle(new MediaSyncCompleted($syncResult));

        self::assertModelExists($songs[0]);
        self::assertModelExists($songs[3]);
        self::assertModelMissing($songs[1]);
        self::assertModelMissing($songs[2]);
    }
}
