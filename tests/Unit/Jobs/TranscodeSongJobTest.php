<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Jobs\TranscodeSongJob;
use App\Models\Song;
use App\Services\Transcoding\LocalTranscodingStrategy;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscodeSongJobTest extends TestCase
{
    #[Test]
    public function transcodeTheSongThroughItsStorageStrategy(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::LOCAL]);

        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($song, 256)
            ->andReturn('/transcodes/256/song.m4a');

        (new TranscodeSongJob($song, 256))->handle();
    }
}
