<?php

namespace App\Exceptions\Subsonic;

class DataNotFoundException extends SubsonicException
{
    public function getSubsonicErrorCode(): int
    {
        return 70;
    }

    protected static function defaultMessage(): string
    {
        return 'The requested data was not found.';
    }
}
