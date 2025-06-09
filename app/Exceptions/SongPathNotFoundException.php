<?php

namespace App\Exceptions;

use RuntimeException;

class SongPathNotFoundException extends RuntimeException
{
    public static function create(string $path): self
    {
        return new static("The song at path $path cannot be found.");
    }
}
