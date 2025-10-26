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
    #[Test]
    public function dispatchJobToGenerateThumbnailUponCreation(): void
    {
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        self::restoreObserver();

        Album::factory()->create();
    }

    #[Test]
    public function dispatchJobToGenerateThumbnailUponUpdate(): void
    {
        Dispatcher::expects('dispatch')->once()->with(Mockery::type(GenerateAlbumThumbnailJob::class));

        /** @var Album $album */
        $album = Album::factory()->create();

        self::restoreObserver();

        $album->cover = 'new-cover.webp';
        $album->save();
    }

    #[Test]
    public function doNotDispatchJobToGenerateThumbnailIfCoverIsEmpty(): void
    {
        Dispatcher::expects('dispatch')->never();

        /** @var Album $album */
        $album = Album::factory()->create();

        self::restoreObserver();

        $album->cover = '';
        $album->save();

        // create another album to ensure the observer is still not triggered
        Album::factory()->create(['cover' => '']);
    }

    private static function restoreObserver(): void
    {
        // restore the observer, as it's been "forgotten" during the parent setup
        Album::observe(AlbumObserver::class);
    }
}
