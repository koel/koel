<?php

namespace Tests\Unit\Services\SongStorages;

use App\Exceptions\KoelPlusRequiredException;
use App\Services\SongStorages\S3CompatibleStorage;
use Tests\TestCase;

class S3CompatibleStorageTest extends TestCase
{
    public function testSupported(): void
    {
        self::expectException(KoelPlusRequiredException::class);
        app(S3CompatibleStorage::class);
    }
}
