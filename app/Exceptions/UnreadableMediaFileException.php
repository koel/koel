<?php

namespace App\Exceptions;

use RuntimeException;

class UnreadableMediaFileException extends RuntimeException
{
    public static function create(string $reason): self
    {
        return new self($reason);
    }
}
