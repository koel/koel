<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class InvalidTwoFactorLoginTokenException extends RuntimeException
{
    public static function create(?Throwable $previous = null): self
    {
        return new self('Invalid or expired two-factor login token.', previous: $previous);
    }
}
