<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidOneTimeTokenException extends RuntimeException
{
    public static function create(?Throwable $previous = null): self
    {
        return new self('Invalid or expired one-time token.', previous: $previous);
    }
}
