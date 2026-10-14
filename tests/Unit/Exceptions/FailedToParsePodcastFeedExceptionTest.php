<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\FailedToParsePodcastFeedException;
use PDOException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FailedToParsePodcastFeedExceptionTest extends TestCase
{
    #[Test]
    public function wrapPdoExceptionWithStringCode(): void
    {
        $pdoException = new class('too many SQL variables') extends PDOException {
            protected $code = 'HY000';
        };

        $exception = FailedToParsePodcastFeedException::create('https://example.com/feed.xml', $pdoException);

        self::assertSame($pdoException, $exception->getPrevious());
    }
}
