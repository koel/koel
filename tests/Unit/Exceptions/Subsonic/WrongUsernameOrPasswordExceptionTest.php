<?php

namespace Tests\Unit\Exceptions\Subsonic;

use App\Exceptions\Subsonic\WrongUsernameOrPasswordException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WrongUsernameOrPasswordExceptionTest extends TestCase
{
    #[Test]
    public function mapsToSubsonicCode40WithDefaultMessage(): void
    {
        $exception = new WrongUsernameOrPasswordException();

        self::assertSame(40, $exception->getSubsonicErrorCode());
        self::assertSame('Wrong username or password.', $exception->getSubsonicErrorMessage());
    }

    #[Test]
    public function acceptsCustomMessage(): void
    {
        $exception = new WrongUsernameOrPasswordException('Token signature invalid.');

        self::assertSame(40, $exception->getSubsonicErrorCode());
        self::assertSame('Token signature invalid.', $exception->getSubsonicErrorMessage());
    }
}
