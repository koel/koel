<?php

namespace Tests\Unit\Exceptions\Subsonic;

use App\Exceptions\Subsonic\RequiredParameterMissingException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequiredParameterMissingExceptionTest extends TestCase
{
    #[Test]
    public function mapsToSubsonicCode10WithDefaultMessage(): void
    {
        $exception = new RequiredParameterMissingException();

        self::assertSame(10, $exception->getSubsonicErrorCode());
        self::assertSame('Required parameter is missing.', $exception->getSubsonicErrorMessage());
    }

    #[Test]
    public function acceptsCustomMessage(): void
    {
        $exception = new RequiredParameterMissingException('Artist id required.');

        self::assertSame(10, $exception->getSubsonicErrorCode());
        self::assertSame('Artist id required.', $exception->getSubsonicErrorMessage());
    }
}
