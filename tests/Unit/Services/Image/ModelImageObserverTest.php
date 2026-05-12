<?php

namespace Tests\Unit\Services\Image;

use App\Models\Playlist;
use App\Services\Image\ModelImageObserver;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ModelImageObserverTest extends TestCase
{
    #[Test]
    public function onModelUpdatingDeletesTheOriginalImageWhenTheFieldIsDirty(): void
    {
        $playlist = self::makePlaylistWithDirtyCover(original: 'old.webp', current: 'new.webp');

        File::expects('delete')->with([image_storage_path('old.webp')]);

        ModelImageObserver::make('cover')->onModelUpdating($playlist);
    }

    #[Test]
    public function onModelUpdatingNoOpsWhenTheFieldIsNotDirty(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        File::expects('delete')->never();

        ModelImageObserver::make('cover')->onModelUpdating($playlist);
    }

    #[Test]
    public function onModelDeletedDeletesTheCurrentImage(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        File::expects('delete')->with([image_storage_path('cover.webp')]);

        ModelImageObserver::make('cover')->onModelDeleted($playlist);
    }

    #[Test]
    public function deletesTheThumbnailWhenConfigured(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        File::expects('delete')->with([
            image_storage_path('cover.webp'),
            image_storage_path('cover_thumb.webp'),
        ]);

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);
    }

    #[Test]
    public function thumbnailIsAlsoDeletedOnUpdate(): void
    {
        $playlist = self::makePlaylistWithDirtyCover(original: 'old.webp', current: 'new.webp');

        File::expects('delete')->with([
            image_storage_path('old.webp'),
            image_storage_path('old_thumb.webp'),
        ]);

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelUpdating($playlist);
    }

    #[Test]
    public function noOpsWhenTheFieldIsNull(): void
    {
        $playlist = self::makePlaylistWithCleanCover(null);

        File::expects('delete')->never();

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);
    }

    #[Test]
    public function noOpsWhenTheFieldIsAnEmptyString(): void
    {
        $playlist = self::makePlaylistWithCleanCover('');

        File::expects('delete')->never();

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);
    }

    #[Test]
    public function thumbnailDerivationPreservesTheExtension(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.with.dots.png');

        File::expects('delete')->with([
            image_storage_path('cover.with.dots.png'),
            image_storage_path('cover.with.dots_thumb.png'),
        ]);

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);
    }

    #[Test]
    public function fileSystemErrorsAreRescued(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        File::expects('delete')->andThrow(new RuntimeException('disk gone'));

        ModelImageObserver::make('cover')->onModelDeleted($playlist);

        // No exception bubbles up — rescue() swallowed it. Reaching this line is the assertion.
    }

    private static function makePlaylistWithDirtyCover(?string $original, ?string $current): Playlist
    {
        $playlist = Playlist::factory()->makeOne(['cover' => $original]);
        $playlist->syncOriginal();
        $playlist->cover = $current;

        return $playlist;
    }

    private static function makePlaylistWithCleanCover(?string $value): Playlist
    {
        $playlist = Playlist::factory()->makeOne(['cover' => $value]);
        $playlist->syncOriginal();

        return $playlist;
    }
}
