<?php

namespace Tests\Unit\Services\Image;

use App\Services\Image\ImageStorage;
use App\Services\Image\LegacyArtworkMigrator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyArtworkMigratorTest extends TestCase
{
    private LegacyArtworkMigrator $migrator;

    public function setUp(): void
    {
        parent::setUp();

        $this->migrator = app(LegacyArtworkMigrator::class);
        File::deleteDirectory(public_path(LegacyArtworkMigrator::LEGACY_DIR));
    }

    public function tearDown(): void
    {
        File::deleteDirectory(public_path(LegacyArtworkMigrator::LEGACY_DIR));

        parent::tearDown();
    }

    private static function createLegacyImage(string $fileName, string $contents = 'legacy-bytes'): void
    {
        File::ensureDirectoryExists(public_path(LegacyArtworkMigrator::LEGACY_DIR));
        File::put(public_path(LegacyArtworkMigrator::LEGACY_DIR . '/' . $fileName), $contents);
    }

    #[Test]
    public function moveLegacyImagesOntoTheConfiguredDisk(): void
    {
        $disk = Storage::fake(ImageStorage::DISK);
        self::createLegacyImage('cover.webp');

        self::assertTrue($this->migrator->migrate());

        $disk->assertExists('cover.webp');
        self::assertSame('legacy-bytes', $disk->get('cover.webp'));
        self::assertDirectoryDoesNotExist(public_path(LegacyArtworkMigrator::LEGACY_DIR));
    }

    #[Test]
    public function keepWhatTheDiskAlreadyHas(): void
    {
        $disk = Storage::fake(ImageStorage::DISK);
        $disk->put('cover.webp', 'current-bytes');
        self::createLegacyImage('cover.webp');

        self::assertTrue($this->migrator->migrate());

        self::assertSame('current-bytes', $disk->get('cover.webp'));
        self::assertDirectoryDoesNotExist(public_path(LegacyArtworkMigrator::LEGACY_DIR));
    }

    #[Test]
    public function keepTheLegacyFilesWhenTheDiskRefusesThem(): void
    {
        Storage::fake(ImageStorage::DISK);
        self::createLegacyImage('cover.webp');

        $this->mock(ImageStorage::class);
        Storage::shouldReceive('disk')
            ->with(ImageStorage::DISK)
            ->andReturn(\Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class, static function ($disk): void {
                $disk->allows('exists')->andReturnFalse();
                $disk->allows('put')->andReturnFalse();
            }));

        self::assertFalse($this->migrator->migrate());

        self::assertFileExists(public_path(LegacyArtworkMigrator::LEGACY_DIR . '/cover.webp'));
    }

    #[Test]
    public function countWhatIsWaiting(): void
    {
        self::assertSame(0, $this->migrator->pendingCount());
        self::assertFalse($this->migrator->hasLegacyDirectory());

        self::createLegacyImage('one.webp');
        self::createLegacyImage('two.webp');

        self::assertSame(2, $this->migrator->pendingCount());
        self::assertTrue($this->migrator->hasLegacyDirectory());
    }
}
