<?php

namespace App\Exceptions;

use App\Enums\SongStorageType;
use DomainException;

class NonCloudStorageException extends DomainException
{
    public static function create(SongStorageType $type): self
    {
        return new self("Not a cloud storage type: {$type->value}.");
    }
}
