<?php

namespace Tests\Unit\Exceptions\Subsonic;

use App\Exceptions\Subsonic\DataNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DataNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function mapsToSubsonicCode70WithDefaultMessage(): void
    {
        $exception = new DataNotFoundException();

        self::assertSame(70, $exception->getSubsonicErrorCode());
        self::assertSame('The requested data was not found.', $exception->getSubsonicErrorMessage());
    }

    #[Test]
    public function acceptsCustomMessage(): void
    {
        $exception = new DataNotFoundException('Podcast not found.');

        self::assertSame(70, $exception->getSubsonicErrorCode());
        self::assertSame('Podcast not found.', $exception->getSubsonicErrorMessage());
    }
}
