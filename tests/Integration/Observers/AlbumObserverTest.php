<?php

namespace Tests\Integration\Observers;

use App\Facades\Dispatcher;
use App\Jobs\GenerateAlbumThumbnailJob;
use App\Models\Album;
use App\Models\Artist;
use App\Observers\AlbumObserver;
use App\Services\Image\ImageStorage;
use App\Services\LibrarySummaryService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AlbumObserverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Re-bind the real AlbumObserver (TestCase replaces `saved` with a no-op).
        $this->app->instance(
            AlbumObserver::class,
            new AlbumObserver($this->app->make(ImageStorage::class), $this->app->make(LibrarySummaryService::class)),
        );
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
        Dispatcher::expects('dispatch')->with(Mockery::type(GenerateAlbumThumbnailJob::class))->never();

        $album = Album::factory()->createOne(['cover' => '']);

        $album->cover = '';
        $album->save();

        Album::factory()->createOne(['cover' => '']);
    }

    #[Test]
    public function changingTheCoverRefreshesTheAlbumArtists(): void
    {
        $user = create_user();
        $album = Album::factory()->for(Artist::factory()->createOne(['image' => '']))->createOne(['cover' => '']);
        $librarySummaryService = app(LibrarySummaryService::class);
        Dispatcher::shouldReceive('dispatch');

        self::assertNull($librarySummaryService
            ->getAlbumArtistsWithoutFavoritesOrRatings($user)
            ->sole()->has_album_cover);

        $album->cover = 'new-cover.webp';
        $album->save();

        self::assertNotNull($librarySummaryService
            ->getAlbumArtistsWithoutFavoritesOrRatings($user)
            ->sole()->has_album_cover);
    }
}
