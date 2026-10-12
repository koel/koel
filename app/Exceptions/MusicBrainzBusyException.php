<?php

namespace App\Exceptions;

use RuntimeException;

final class MusicBrainzBusyException extends RuntimeException
{
    public static function create(): self
    {
        return new self('No MusicBrainz request slot is free right now.');
    }
}
