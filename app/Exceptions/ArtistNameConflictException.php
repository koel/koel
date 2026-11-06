<?php

namespace App\Exceptions;

use App\Models\Artist;
use DomainException;

class ArtistNameConflictException extends DomainException
{
    public function __construct(
        public readonly Artist $existingArtist,
    ) {
        parent::__construct('An artist with the same name already exists.');
    }
}
