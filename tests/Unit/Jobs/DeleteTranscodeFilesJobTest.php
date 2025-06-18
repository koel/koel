<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Jobs\DeleteTranscodeFiles;
use App\Services\Transcoding\CloudTranscodingStrategy;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Values\Transcoding\TranscodeFileInfo;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeleteTranscodeFilesJobTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        $files = collect([
            TranscodeFileInfo::make('path/to/transcode.m4a', SongStorageType::LOCAL),
            TranscodeFileInfo::make('key.m4a', SongStorageType::S3),
        ]);

        $localStrategy = $this->mock(LocalTranscodingStrategy::class);

        $localStrategy->shouldReceive('deleteTranscodeFile')
            ->with('path/to/transcode.m4a', SongStorageType::LOCAL)
            ->once();

        $cloudStrategy = $this->mock(CloudTranscodingStrategy::class);

        $cloudStrategy->shouldReceive('deleteTranscodeFile')
            ->with('key.m4a', SongStorageType::S3)
            ->once();


        $job = new DeleteTranscodeFiles($files);
        $job->handle();
    }
}
