<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Jobs\CreateSongPreviewJob;
use App\Models\Song;
use App\Services\Transcoding\LocalTranscodingStrategy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateSongPreviewJobTest extends TestCase
{
    #[Test]
    public function createThePreviewThroughTheSongsStorageStrategy(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::LOCAL]);

        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getPreviewLocation')
            ->with($song)
            ->andReturn('/transcodes/64/preview.m4a');

        (new CreateSongPreviewJob($song, 300))->handle();
    }
}
