<?php

namespace App\Exceptions\Subsonic;

class GenericErrorException extends SubsonicException
{
    public function getSubsonicErrorCode(): int
    {
        return 0;
    }

    protected static function defaultMessage(): string
    {
        return 'A generic error occurred.';
    }
}
