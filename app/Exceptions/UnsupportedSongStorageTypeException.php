<?php

namespace App\Exceptions;

use App\Enums\SongStorageType;
use InvalidArgumentException;

class UnsupportedSongStorageTypeException extends InvalidArgumentException
{
    public static function create(SongStorageType $storageType): self
    {
        return new self("Unsupported song storage type: $storageType->value");
    }
}
