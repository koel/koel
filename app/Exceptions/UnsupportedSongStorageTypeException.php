<?php

namespace App\Exceptions;

use Exception;

class UnsupportedSongStorageTypeException extends Exception
{
    private function __construct(string $storageType)
    {
        parent::__construct("Unsupported song storage type: $storageType");
    }

    public static function create(string $storageType): self
    {
        return new self($storageType);
    }
}
