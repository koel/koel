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
