<?php

namespace Tests\Unit\Models;

use App\Enums\SongStorageType;
use App\Models\DuplicateUpload;
use App\Models\Song;
use App\Models\User;
use App\Values\Scanning\ScanConfiguration;
use App\Values\UploadReference;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DuplicateUploadTest extends TestCase
{
    #[Test]
    public function belongsToUser(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne();

        self::assertInstanceOf(User::class, $duplicateUpload->user);
    }

    #[Test]
    public function belongsToExistingSong(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne();

        self::assertInstanceOf(Song::class, $duplicateUpload->existingSong);
    }

    #[Test]
    public function toUploadReference(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne([
            'location' => '/var/media/koel/some-file.mp3',
            'storage' => SongStorageType::LOCAL,
        ]);

        $reference = $duplicateUpload->toUploadReference();

        self::assertInstanceOf(UploadReference::class, $reference);
        self::assertSame('/var/media/koel/some-file.mp3', $reference->location);
        self::assertSame('/var/media/koel/some-file.mp3', $reference->localPath);
    }

    #[Test]
    public function toScanConfiguration(): void
    {
        $duplicateUpload = DuplicateUpload::factory()->createOne([
            'make_public' => true,
            'extract_folder_structure' => false,
        ]);

        $config = $duplicateUpload->toScanConfiguration();

        self::assertInstanceOf(ScanConfiguration::class, $config);
        self::assertTrue($duplicateUpload->user->is($config->owner));
        self::assertTrue($config->makePublic);
        self::assertFalse($config->extractFolderStructure);
    }
}
