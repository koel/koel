<?php

namespace App\Exceptions;

use DomainException;

class AlbumNameConflictException extends DomainException
{
    public function __construct()
    {
        parent::__construct('An album with the same name already exists for this artist.');
    }
}
