<?php

namespace Tests\Integration\KoelPlus\Enums;

use App\Enums\SongStorageType;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

class SongStorageTypeTest extends PlusTestCase
{
    #[Test]
    public function supported(): void
    {
        self::assertTrue(collect(SongStorageType::cases())->every(
            static fn (SongStorageType $type) => $type->supported()
        ));
    }
}
