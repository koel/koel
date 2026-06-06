<?php

namespace Tests\Unit\Exceptions\Subsonic;

use App\Exceptions\Subsonic\UnsupportedAlbumListTypeException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnsupportedAlbumListTypeExceptionTest extends TestCase
{
    #[Test]
    public function createCarriesTheTypeInTheMessage(): void
    {
        $exception = UnsupportedAlbumListTypeException::create('bogus');

        self::assertSame('Unsupported album list type: bogus', $exception->getMessage());
    }

    #[Test]
    public function mapsToSubsonicCodeZeroWithItsMessage(): void
    {
        $exception = UnsupportedAlbumListTypeException::create('byMood');

        self::assertSame(0, $exception->getSubsonicErrorCode());
        self::assertSame('Unsupported album list type: byMood', $exception->getSubsonicErrorMessage());
    }
}
