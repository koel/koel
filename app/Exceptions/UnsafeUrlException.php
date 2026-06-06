<?php

namespace App\Exceptions;

use RuntimeException;

class UnsafeUrlException extends RuntimeException
{
    public static function forUrl(string $url): self
    {
        return new self(sprintf('Refusing to fetch URL that does not resolve to a public host: %s', $url));
    }
}
