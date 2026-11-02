<?php

namespace Tests\Unit\Jobs;

use App\Enums\SongStorageType;
use App\Exceptions\DuplicateSongUploadException;
use App\Exceptions\SongUploadFailedException;
use App\Jobs\HandlePresignedSongUploadJob;
use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Repositories\AlbumRepository;
use App\Repositories\SongRepository;
use App\Responses\SongUploadDuplicatedResponse;
use App\Responses\SongUploadFailedResponse;
use App\Services\SongStorages\SongStorage;
use App\Services\Upload\UploadService;
use ErrorException;
use Exception;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Support\Arr;
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
    }

    #[Test]
    public function broadcastAQueuedDuplicateInsteadOfFailing(): void
    {
        Event::fake(SongUploadDuplicatedResponse::class);
        $this->uploadService->expects('handleStoredUpload')->andThrow($this->duplicateException);

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
        $this->uploadService->expects('handleStoredUpload')->andThrow($this->duplicateException);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());

        $this->expectExceptionObject($this->duplicateException);

        $job->handle($this->storage, $this->uploadService, app(SongRepository::class), app(AlbumRepository::class));
    }

    #[Test]
    public function deleteTheStoredFileWhenTheLocalCopyFailsForGood(): void
    {
        Event::fake(SongUploadFailedResponse::class);

        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::S3]);
        $storage->expects('getLocalPath')->andThrow(new Exception('Permission denied'));
        $storage->expects('delete')->with('song.mp3');
        $this->instance(SongStorage::class, $storage);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->setJob(Mockery::mock(Job::class));

        try {
            app()->call([$job, 'handle']);
        } catch (Exception $e) {
            $job->failed($e);
        }

        Event::assertDispatched(SongUploadFailedResponse::class);
    }

    #[Test]
    public function hideAnInternalErrorFromTheUploader(): void
    {
        Event::fake(SongUploadFailedResponse::class);
        $this->instance(SongStorage::class, Mockery::spy(SongStorage::class, [
            'getStorageType' => SongStorageType::S3,
        ]));

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->setJob(Mockery::mock(Job::class));
        $job->failed(new ErrorException('copy(https://bucket.host/key): Failed'));

        Event::assertDispatched(
            SongUploadFailedResponse::class,
            static fn (SongUploadFailedResponse $response): bool => (
                Arr::get($response->toArray(), 'message') === 'The file could not be processed.'
            ),
        );
    }

    #[Test]
    public function showAUserFacingErrorToTheUploader(): void
    {
        Event::fake(SongUploadFailedResponse::class);
        $this->instance(SongStorage::class, Mockery::spy(SongStorage::class, [
            'getStorageType' => SongStorageType::S3,
        ]));

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->setJob(Mockery::mock(Job::class));
        $job->failed(SongUploadFailedException::make('The uploaded file could not be stored.'));

        Event::assertDispatched(
            SongUploadFailedResponse::class,
            static fn (SongUploadFailedResponse $response): bool => (
                Arr::get($response->toArray(), 'message') === 'The uploaded file could not be stored.'
            ),
        );
    }

    #[Test]
    public function keepADuplicateUploadWhenFailingForGood(): void
    {
        Event::fake(SongUploadFailedResponse::class);
        DuplicateUpload::factory()->createOne(['location' => 's3://koel/song.mp3']);

        $storage = Mockery::mock(SongStorage::class);
        $storage->expects('delete')->never();
        $this->instance(SongStorage::class, $storage);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->failed(new Exception('Broadcast failed'));
    }

    #[Test]
    public function keepAStoredSongFileWhenFailingForGood(): void
    {
        Event::fake(SongUploadFailedResponse::class);
        Song::factory()->createOne(['path' => 's3://koel/song.mp3', 'storage' => SongStorageType::S3]);

        $storage = Mockery::mock(SongStorage::class);
        $storage->expects('delete')->never();
        $this->instance(SongStorage::class, $storage);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->failed(new Exception('Broadcast failed'));
    }

    #[Test]
    public function stillReportTheFailureWhenTheStoredFileCannotBeDeleted(): void
    {
        Event::fake(SongUploadFailedResponse::class);

        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::S3]);
        $storage->expects('delete')->andThrow(new Exception('Not found'));
        $this->instance(SongStorage::class, $storage);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->setJob(Mockery::mock(Job::class));
        $job->failed(new Exception('Permission denied'));

        Event::assertDispatched(SongUploadFailedResponse::class);
    }

    #[Test]
    public function skipTheFailureBroadcastWhenRunInline(): void
    {
        Event::fake(SongUploadFailedResponse::class);

        $storage = Mockery::mock(SongStorage::class, ['getStorageType' => SongStorageType::S3]);
        $storage->expects('delete')->with('song.mp3');
        $this->instance(SongStorage::class, $storage);

        $job = new HandlePresignedSongUploadJob('s3://koel/song.mp3', 'pending/song.mp3', create_user());
        $job->failed(new Exception('Permission denied'));

        Event::assertNotDispatched(SongUploadFailedResponse::class);
    }
}
