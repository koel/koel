<?php

namespace App\Exceptions\Subsonic;

class UnsupportedAlbumListTypeException extends GenericErrorException
{
    public static function create(string $type): self
    {
        return new self("Unsupported album list type: $type");
    }
}
