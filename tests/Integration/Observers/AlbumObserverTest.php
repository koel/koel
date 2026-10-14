<?php

namespace Tests\Integration\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumSmallCoverJob;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Observers\AlbumObserver;
use App\Services\Image\ImageStorage;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumObserverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Re-bind the real AlbumObserver (TestCase replaces `saved` with a no-op).
        $this->app->instance(AlbumObserver::class, new AlbumObserver($this->app->make(ImageStorage::class)));
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponCreation(): void
    {
        Dispatcher::shouldReceive('dispatch')->with(Mockery::type(GenerateAlbumSmallCoverJob::class));
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        Album::factory()->createOne();
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponUpdate(): void
    {
        $album = Album::factory()->createOne();

        Dispatcher::shouldReceive('dispatch')->with(Mockery::type(GenerateAlbumSmallCoverJob::class));
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        $album->cover = 'new-cover.webp';
        $album->save();
    }

    #[Test]
    public function doNotDispatchJobsIfCoverIsEmpty(): void
    {
        Dispatcher::expects('dispatch')->never();

        $album = Album::factory()->createOne(['cover' => '']);

        $album->cover = '';
        $album->save();

        Album::factory()->createOne(['cover' => '']);
    }

    #[Test]
    public function dispatchJobToGenerateSmallCoverWhenMissing(): void
    {
        Dispatcher::shouldReceive('dispatch')->with(Mockery::type(GenerateAlbumThumbnailJob::class));
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumSmallCoverJob::class));

        Album::factory()->createOne(['small_cover' => null]);
    }

    #[Test]
    public function doNotDispatchJobToGenerateSmallCoverWhenPresent(): void
    {
        Dispatcher::shouldReceive('dispatch')->with(Mockery::type(GenerateAlbumThumbnailJob::class));
        Dispatcher::expects('dispatch')->with(Mockery::type(GenerateAlbumSmallCoverJob::class))->never();

        Album::factory()->createOne(['small_cover' => 'small.webp']);
    }

    #[Test]
    public function replacingCoverRemovesSmallCover(): void
    {
        Dispatcher::shouldReceive('dispatch');
        $disk = Storage::fake(ImageStorage::DISK);
        $disk->put('small.webp', 'small-bytes');

        $album = Album::factory()->createOne(['cover' => 'cover.webp', 'small_cover' => 'small.webp']);

        $album->update(['cover' => 'new-cover.webp']);

        self::assertNull($album->refresh()->small_cover);
        $disk->assertMissing('small.webp');
    }

    #[Test]
    public function deletingAlbumRemovesSmallCover(): void
    {
        Dispatcher::shouldReceive('dispatch');
        $disk = Storage::fake(ImageStorage::DISK);
        $disk->put('small.webp', 'small-bytes');

        Album::factory()->createOne(['cover' => 'cover.webp', 'small_cover' => 'small.webp'])->delete();

        $disk->assertMissing('small.webp');
    }
}
