<?php

namespace Tests\Unit\Jobs;

use App\Exceptions\DuplicateSongUploadException;
use App\Jobs\HandlePresignedSongUploadJob;
use App\Models\DuplicateUpload;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadDuplicatedResponse;
use App\Services\SongStorages\SongStorage;
use App\Services\Upload\UploadService;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class HandlePresignedSongUploadJobTest extends TestCase
{
    private SongStorage|MockInterface $storage;
    private UploadService|MockInterface $uploadService;
    private DuplicateSongUploadException $duplicateException;

    public function setUp(): void
    {
        parent::setUp();

        $this->storage = Mockery::mock(SongStorage::class, [
            'getLocalPath' => '/tmp/song.mp3',
            'getOriginalFileName' => 'song.mp3',
        ]);

        $this->duplicateException = DuplicateSongUploadException::create(
            '/tmp/song.mp3',
            DuplicateUpload::factory()->createOne(),
        );

        $this->uploadService = Mockery::mock(UploadService::class);
        $this->uploadService->expects('handleStoredUpload')->andThrow($this->duplicateException);
    }

    #[Test]
    public function broadcastAQueuedDuplicateInsteadOfFailing(): void
    {
        Event::fake(SongUploadDuplicatedResponse::class);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->setJob(Mockery::mock(Job::class));

        $result = $job->handle(
            $this->storage,
            $this->uploadService,
            app(SongRepository::class),
            app(AlbumRepository::class),
        );

        self::assertNull($result);
        Event::assertDispatched(SongUploadDuplicatedResponse::class);
    }

    #[Test]
    public function rethrowADuplicateWhenRunInline(): void
    {
        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());

        $this->expectExceptionObject($this->duplicateException);

        $job->handle($this->storage, $this->uploadService, app(SongRepository::class), app(AlbumRepository::class));
    }
}
