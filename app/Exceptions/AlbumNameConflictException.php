<?php

namespace App\Exceptions;

use App\Models\Album;
use DomainException;

class AlbumNameConflictException extends DomainException
{
    public function __construct(
        public readonly Album $existingAlbum,
    ) {
        parent::__construct('An album with the same name already exists for this artist.');
    }
}
