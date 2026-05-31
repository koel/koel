<?php

namespace Tests\Unit\Exceptions\Subsonic;

use App\Exceptions\Subsonic\GenericErrorException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenericErrorExceptionTest extends TestCase
{
    #[Test]
    public function mapsToSubsonicCodeZeroWithDefaultMessage(): void
    {
        $exception = new GenericErrorException();

        self::assertSame(0, $exception->getSubsonicErrorCode());
        self::assertSame('A generic error occurred.', $exception->getSubsonicErrorMessage());
    }

    #[Test]
    public function acceptsCustomMessage(): void
    {
        $exception = new GenericErrorException('Something specific broke.');

        self::assertSame(0, $exception->getSubsonicErrorCode());
        self::assertSame('Something specific broke.', $exception->getSubsonicErrorMessage());
    }
}
