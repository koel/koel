<?php

namespace Tests\Integration\KoelPlus\Enums;

use App\Enums\SongStorageType;
use Tests\PlusTestCase;

class SongStorageTypeTest extends PlusTestCase
{
    public function testSupported(): void
    {
        self::assertTrue(collect(SongStorageType::cases())->every(
            static fn (SongStorageType $type) => $type->supported()
        ));
    }
}
