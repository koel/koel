<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidLoginTokenException extends RuntimeException
{
    public static function create(?Throwable $previous = null): self
    {
        return new self('Invalid or expired login token.', previous: $previous);
    }
}
