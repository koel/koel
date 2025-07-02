<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Jobs\DeleteTranscodeFilesJob;
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

        $this->mock(LocalTranscodingStrategy::class)
            ->expects('deleteTranscodeFile')
            ->with('path/to/transcode.m4a', SongStorageType::LOCAL);

        $this->mock(CloudTranscodingStrategy::class)
            ->expects('deleteTranscodeFile')
            ->with('key.m4a', SongStorageType::S3);

        $job = new DeleteTranscodeFilesJob($files);
        $job->handle();
    }
}
