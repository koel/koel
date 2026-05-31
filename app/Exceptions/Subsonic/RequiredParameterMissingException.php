<?php

namespace App\Exceptions\Subsonic;

class RequiredParameterMissingException extends SubsonicException
{
    public function getSubsonicErrorCode(): int
    {
        return 10;
    }

    protected static function defaultMessage(): string
    {
        return 'Required parameter is missing.';
    }
}
