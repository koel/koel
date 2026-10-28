<?php

namespace Tests\Feature\Commands;

use App\Enums\SongStorageType;
use App\Facades\Dispatcher;
use App\Jobs\CreateSongPreviewJob;
use App\Models\Song;
use App\Models\Transcode;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class CreateSongPreviewsCommandTest extends TestCase
{
    #[Test]
    public function createPreviewsForSongsWithoutOne(): void
    {
        Song::factory()->createMany(3);
        Song::factory()->asEpisode()->createOne();
        Song::factory()->createOne(['storage' => SongStorageType::S3, 'path' => 's3://koel/song.mp3']);
        Transcode::factory()->asPreview()->for(Song::factory()->createOne())->createOne();
        Transcode::factory()->for(Song::factory()->createOne())->createOne();

        Dispatcher::expects('dispatch')->times(3)->with(Mockery::type(CreateSongPreviewJob::class));
        Dispatcher::expects('dispatch')->andThrow(new RuntimeException('ffmpeg failed'));

        $this
            ->artisan('koel:create-song-previews')
            ->expectsTable(['Result', 'Songs'], [
                ['Created or queued',                 3],
                ['Failed',                            1],
                ['Skipped (storage needs Koel Plus)', 1],
            ])
            ->assertSuccessful();
    }

    #[Test]
    public function doNothingWhenEverySongHasAPreview(): void
    {
        Transcode::factory()->asPreview()->for(Song::factory()->createOne())->createOne();

        Dispatcher::expects('dispatch')->never();

        $this
            ->artisan('koel:create-song-previews')
            ->expectsOutput('Every song already has a preview.')
            ->assertSuccessful();
    }
}
