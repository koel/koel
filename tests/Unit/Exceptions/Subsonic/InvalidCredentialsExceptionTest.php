<?php

namespace Tests\Unit\Exceptions\Subsonic;

use App\Exceptions\Subsonic\InvalidCredentialsException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvalidCredentialsExceptionTest extends TestCase
{
    #[Test]
    public function mapsToSubsonicCode40WithDefaultMessage(): void
    {
        $exception = new InvalidCredentialsException();

        self::assertSame(40, $exception->getSubsonicErrorCode());
        self::assertSame('Wrong username or password.', $exception->getSubsonicErrorMessage());
    }

    #[Test]
    public function acceptsCustomMessage(): void
    {
        $exception = new InvalidCredentialsException('Token signature invalid.');

        self::assertSame(40, $exception->getSubsonicErrorCode());
        self::assertSame('Token signature invalid.', $exception->getSubsonicErrorMessage());
    }
}
