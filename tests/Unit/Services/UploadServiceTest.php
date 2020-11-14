<?php

namespace Tests\Unit\Services;

use App\Exceptions\MediaPathNotSetException;
use App\Exceptions\SongUploadFailedException;
use App\Models\Setting;
use App\Models\Song;
use App\Services\FileSynchronizer;
use App\Services\MediaSyncService;
use App\Services\UploadService;
use Illuminate\Http\UploadedFile;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UploadServiceTest extends TestCase
{
    private $fileSynchronizer;
    private $uploadService;

    public function setUp(): void
    {
        parent::setUp();
        $this->fileSynchronizer = Mockery::mock(FileSynchronizer::class);
        $this->uploadService = new UploadService($this->fileSynchronizer);
    }

    public function testHandleUploadedFileWithMediaPathNotSet(): void
    {
        Setting::set('media_path', null);
        self::expectException(MediaPathNotSetException::class);
        $this->uploadService->handleUploadedFile(Mockery::mock(UploadedFile::class));
    }

    public function testHandleUploadedFileFails(): void
    {
        Setting::set('media_path', '/media/koel');

        /** @var UploadedFile|MockInterface $file */
        $file = Mockery::mock(UploadedFile::class);

        $file->shouldReceive('getClientOriginalName')
            ->andReturn('foo.mp3');

        $file->shouldReceive('move')
            ->once()
            ->with('/media/koel/__KOEL_UPLOADS__/', 'foo.mp3');

        $this->fileSynchronizer
            ->shouldReceive('setFile')
            ->once()
            ->with('/media/koel/__KOEL_UPLOADS__/foo.mp3');

        $this->fileSynchronizer
            ->shouldReceive('sync')
            ->once()
            ->with(MediaSyncService::APPLICABLE_TAGS)
            ->andReturn(FileSynchronizer::SYNC_RESULT_BAD_FILE);

        $this->fileSynchronizer
            ->shouldReceive('getSyncError')
            ->once()
            ->andReturn('A monkey ate your file oh no');

        self::expectException(SongUploadFailedException::class);
        self::expectExceptionMessage('A monkey ate your file oh no');
        $this->uploadService->handleUploadedFile($file);
    }

    public function testHandleUploadedFile(): void
    {
        Setting::set('media_path', '/media/koel');

        /** @var UploadedFile|MockInterface $file */
        $file = Mockery::mock(UploadedFile::class);

        $file->shouldReceive('getClientOriginalName')
            ->andReturn('foo.mp3');

        $file->shouldReceive('move')
            ->once()
            ->with('/media/koel/__KOEL_UPLOADS__/', 'foo.mp3');

        $this->fileSynchronizer
            ->shouldReceive('setFile')
            ->once()
            ->with('/media/koel/__KOEL_UPLOADS__/foo.mp3');

        $this->fileSynchronizer
            ->shouldReceive('sync')
            ->once()
            ->with(MediaSyncService::APPLICABLE_TAGS)
            ->andReturn(FileSynchronizer::SYNC_RESULT_SUCCESS);

        $song = new Song();

        $this->fileSynchronizer
            ->shouldReceive('getSong')
            ->once()
            ->andReturn($song);

        self::assertSame($song, $this->uploadService->handleUploadedFile($file));
    }
}
