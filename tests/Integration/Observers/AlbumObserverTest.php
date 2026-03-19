<?php

namespace Tests\Integration\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Observers\AlbumObserver;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlbumObserverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Re-bind the real AlbumObserver (TestCase replaces `saved` with a no-op).
        $this->app->bind(AlbumObserver::class, static fn () => new AlbumObserver());
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponCreation(): void
    {
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        Album::factory()->createOne();
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponUpdate(): void
    {
        $album = Album::factory()->createOne();

        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        $album->cover = 'new-cover.webp';
        $album->save();
    }

    #[Test]
    public function doNotDispatchJobToGenerateThumbnailIfCoverIsEmpty(): void
    {
        $album = Album::factory()->createOne(['cover' => '']);

        Dispatcher::expects('dispatch')->with(Mockery::type(GenerateAlbumThumbnailJob::class))->never();

        $album->cover = '';
        $album->save();

        Album::factory()->createOne(['cover' => '']);
    }
}
