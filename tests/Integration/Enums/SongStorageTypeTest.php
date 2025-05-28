<?php

namespace Tests\Integration\Enums;

use App\Enums\SongStorageType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongStorageTypeTest extends TestCase
{
    #[Test]
    public function supported(): void
    {
        self::assertTrue(SongStorageType::LOCAL->supported());
        self::assertTrue(SongStorageType::S3_LAMBDA->supported());
        self::assertFalse(SongStorageType::SFTP->supported());
        self::assertFalse(SongStorageType::DROPBOX->supported());
        self::assertFalse(SongStorageType::S3->supported());
    }

    #[Test]
    public function supportsFolderStructureExtraction(): void
    {
        self::assertTrue(SongStorageType::LOCAL->supportsFolderStructureExtraction());
        self::assertFalse(SongStorageType::S3_LAMBDA->supportsFolderStructureExtraction());
        self::assertFalse(SongStorageType::SFTP->supportsFolderStructureExtraction());
        self::assertFalse(SongStorageType::DROPBOX->supportsFolderStructureExtraction());
        self::assertFalse(SongStorageType::S3->supportsFolderStructureExtraction());
    }
}
