<?php

namespace Tests\Unit\Services\Image;

use App\Models\Playlist;
use App\Services\Image\ImageStorage;
use App\Services\Image\ModelImageObserver;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ModelImageObserverTest extends TestCase
{
    /** @return \Illuminate\Contracts\Filesystem\Filesystem */
    private static function fakeDiskWith(string ...$fileNames)
    {
        $disk = Storage::fake(ImageStorage::DISK);

        foreach ($fileNames as $fileName) {
            $disk->put($fileName, 'dummy');
        }

        return $disk;
    }

    #[Test]
    public function onModelUpdatingDeletesTheOriginalImageWhenTheFieldIsDirty(): void
    {
        $playlist = self::makePlaylistWithDirtyCover(original: 'old.webp', current: 'new.webp');

        $disk = self::fakeDiskWith('old.webp');

        ModelImageObserver::make('cover')->onModelUpdating($playlist);

        $disk->assertMissing('old.webp');
    }

    #[Test]
    public function onModelUpdatingNoOpsWhenTheFieldIsNotDirty(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        $disk = self::fakeDiskWith('cover.webp');

        ModelImageObserver::make('cover')->onModelUpdating($playlist);

        $disk->assertExists('cover.webp');
    }

    #[Test]
    public function onModelDeletedDeletesTheCurrentImage(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        $disk = self::fakeDiskWith('cover.webp');

        ModelImageObserver::make('cover')->onModelDeleted($playlist);

        $disk->assertMissing('cover.webp');
    }

    #[Test]
    public function deletesTheThumbnailWhenConfigured(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        $disk = self::fakeDiskWith('cover.webp', 'cover_thumb.webp');

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);

        $disk->assertMissing('cover.webp');
        $disk->assertMissing('cover_thumb.webp');
    }

    #[Test]
    public function thumbnailIsAlsoDeletedOnUpdate(): void
    {
        $playlist = self::makePlaylistWithDirtyCover(original: 'old.webp', current: 'new.webp');

        $disk = self::fakeDiskWith('old.webp', 'old_thumb.webp');

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelUpdating($playlist);

        $disk->assertMissing('old.webp');
        $disk->assertMissing('old_thumb.webp');
    }

    #[Test]
    public function noOpsWhenTheFieldIsNull(): void
    {
        $playlist = self::makePlaylistWithCleanCover(null);

        $disk = self::fakeDiskWith('cover.webp');

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);

        $disk->assertExists('cover.webp');
    }

    #[Test]
    public function noOpsWhenTheFieldIsAnEmptyString(): void
    {
        $playlist = self::makePlaylistWithCleanCover('');

        $disk = self::fakeDiskWith('cover.webp');

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);

        $disk->assertExists('cover.webp');
    }

    #[Test]
    public function thumbnailDerivationPreservesTheExtension(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.with.dots.png');

        $disk = self::fakeDiskWith('cover.with.dots.png', 'cover.with.dots_thumb.png');

        ModelImageObserver::make('cover', hasThumbnail: true)->onModelDeleted($playlist);

        $disk->assertMissing('cover.with.dots.png');
        $disk->assertMissing('cover.with.dots_thumb.png');
    }

    #[Test]
    public function fileSystemErrorsAreRescued(): void
    {
        $playlist = self::makePlaylistWithCleanCover('cover.webp');

        $this->mock(ImageStorage::class)->expects('delete')->andThrow(new RuntimeException('disk gone'));

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
