<?php

namespace App\Exceptions;

use DomainException;

class ArtistNameConflictException extends DomainException
{
    public function __construct()
    {
        parent::__construct('An artist with the same name already exists.');
    }
}
