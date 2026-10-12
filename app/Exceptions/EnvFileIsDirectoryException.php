<?php

namespace App\Exceptions;

use RuntimeException;

class EnvFileIsDirectoryException extends RuntimeException
{
    public static function create(string $path): self
    {
        return new self(sprintf(
            '%s is a directory. If you mount it with Docker, create the file on the host first.',
            $path,
        ));
    }
}
