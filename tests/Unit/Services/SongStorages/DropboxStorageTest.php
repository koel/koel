<?php

namespace Tests\Unit\Services\SongStorages;

use App\Exceptions\KoelPlusRequiredException;
use App\Services\SongStorages\DropboxStorage;
use Tests\TestCase;

class DropboxStorageTest extends TestCase
{
    public function testSupported(): void
    {
        $this->expectException(KoelPlusRequiredException::class);
        app(DropboxStorage::class);
    }
}
