<?php

namespace Tests\Unit\Services\SongStorages;

use App\Exceptions\KoelPlusRequiredException;
use App\Services\SongStorages\DropboxStorage;
use Tests\TestCase;

class DropboxStorageTest extends TestCase
{
    public function testSupported(): void
    {
        self::expectException(KoelPlusRequiredException::class);
        app(DropboxStorage::class);
    }
}
