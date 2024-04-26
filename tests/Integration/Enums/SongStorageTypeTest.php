<?php

namespace Tests\Integration\Enums;

use App\Enums\SongStorageType;
use Tests\TestCase;

class SongStorageTypeTest extends TestCase
{
    public function testSupported(): void
    {
        self::assertTrue(SongStorageType::LOCAL->supported());
        self::assertTrue(SongStorageType::S3_LAMBDA->supported());
        self::assertFalse(SongStorageType::SFTP->supported());
        self::assertFalse(SongStorageType::DROPBOX->supported());
        self::assertFalse(SongStorageType::S3->supported());
    }
}
