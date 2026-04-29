<?php

namespace Tests\Unit\Services;

use App\Services\ImageLifecycle;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ImageLifecycleTest extends TestCase
{
    private ImageLifecycle $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new ImageLifecycle();
    }

    #[Test]
    public function onReplacedDeletesOldFile(): void
    {
        File::expects('delete')->with([image_storage_path('old.webp')]);

        $this->service->onReplaced('old.webp');
    }

    #[Test]
    public function onReplacedDeletesOldFileAndDerivedThumbnail(): void
    {
        File::expects('delete')->with([
            image_storage_path('old.webp'),
            image_storage_path('old_thumb.webp'),
        ]);

        $this->service->onReplaced('old.webp', hasThumbnail: true);
    }

    #[Test]
    public function onRemovedDeletesFile(): void
    {
        File::expects('delete')->with([image_storage_path('cover.webp')]);

        $this->service->onRemoved('cover.webp');
    }

    #[Test]
    public function onRemovedDeletesFileAndDerivedThumbnail(): void
    {
        File::expects('delete')->with([
            image_storage_path('cover.webp'),
            image_storage_path('cover_thumb.webp'),
        ]);

        $this->service->onRemoved('cover.webp', hasThumbnail: true);
    }

    #[Test]
    public function onReplacedNoOpsWhenFilenameIsNull(): void
    {
        File::expects('delete')->never();

        $this->service->onReplaced(null, hasThumbnail: true);
    }

    #[Test]
    public function onRemovedNoOpsWhenFilenameIsNull(): void
    {
        File::expects('delete')->never();

        $this->service->onRemoved(null, hasThumbnail: true);
    }

    #[Test]
    public function onRemovedNoOpsWhenFilenameIsEmptyString(): void
    {
        File::expects('delete')->never();

        $this->service->onRemoved('', hasThumbnail: true);
    }

    #[Test]
    public function deriveThumbnailPreservesExtension(): void
    {
        File::expects('delete')->with([
            image_storage_path('cover.with.dots.png'),
            image_storage_path('cover.with.dots_thumb.png'),
        ]);

        $this->service->onRemoved('cover.with.dots.png', hasThumbnail: true);
    }

    #[Test]
    public function fileSystemErrorsAreRescued(): void
    {
        File::expects('delete')->andThrow(new \RuntimeException('disk gone'));

        $this->service->onRemoved('cover.webp');

        // No exception bubbles up — rescue() swallowed it. Reaching this line is the assertion.
    }
}
