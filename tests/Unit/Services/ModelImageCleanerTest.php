<?php

namespace Tests\Unit\Services;

use App\Services\ModelImageCleaner;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelImageCleanerTest extends TestCase
{
    private ModelImageCleaner $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new ModelImageCleaner();
    }

    #[Test]
    public function deletesFile(): void
    {
        File::expects('delete')->with([image_storage_path('cover.webp')]);

        $this->service->delete('cover.webp');
    }

    #[Test]
    public function deletesFileAndDerivedThumbnail(): void
    {
        File::expects('delete')->with([
            image_storage_path('cover.webp'),
            image_storage_path('cover_thumb.webp'),
        ]);

        $this->service->delete('cover.webp', hasThumbnail: true);
    }

    #[Test]
    public function noOpsWhenFilenameIsNull(): void
    {
        File::expects('delete')->never();

        $this->service->delete(null, hasThumbnail: true);
    }

    #[Test]
    public function noOpsWhenFilenameIsEmptyString(): void
    {
        File::expects('delete')->never();

        $this->service->delete('', hasThumbnail: true);
    }

    #[Test]
    public function thumbnailDerivationPreservesExtension(): void
    {
        File::expects('delete')->with([
            image_storage_path('cover.with.dots.png'),
            image_storage_path('cover.with.dots_thumb.png'),
        ]);

        $this->service->delete('cover.with.dots.png', hasThumbnail: true);
    }

    #[Test]
    public function fileSystemErrorsAreRescued(): void
    {
        File::expects('delete')->andThrow(new \RuntimeException('disk gone'));

        $this->service->delete('cover.webp');

        // No exception bubbles up — rescue() swallowed it. Reaching this line is the assertion.
    }
}
