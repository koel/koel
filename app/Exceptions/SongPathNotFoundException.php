<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class SongPathNotFoundException extends Exception
{
    private function __construct($message = '', $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function create(string $path): self
    {
        return new static(sprintf('The song at path %s cannot be found.', $path));
    }
}
