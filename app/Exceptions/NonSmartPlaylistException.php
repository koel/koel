<?php

namespace App\Exceptions;

use App\Models\Playlist;
use DomainException;

class NonSmartPlaylistException extends DomainException
{
    public static function create(Playlist $playlist): self
    {
        return new static($playlist->name . ' is not a smart playlist.');
    }
}
