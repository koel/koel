<?php

namespace App\Exceptions;

use App\Enums\SongStorageType;
use Exception;

class UnsupportedSongStorageTypeException extends Exception
{
    private function __construct(SongStorageType $storageType)
    {
        parent::__construct("Unsupported song storage type: $storageType->value");
    }

    public static function create(SongStorageType $storageType): self
    {
        return new self($storageType);
    }
}
