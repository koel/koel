<?php

namespace App\Exceptions;

use DomainException;

class SpotifyIntegrationDisabledException extends DomainException
{
    public static function create(): self
    {
        return new self('Spotify integration is disabled.');
    }
}
