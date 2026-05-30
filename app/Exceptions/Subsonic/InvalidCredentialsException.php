<?php

namespace App\Exceptions\Subsonic;

class InvalidCredentialsException extends SubsonicException
{
    public function getSubsonicErrorCode(): int
    {
        return 40;
    }

    protected static function defaultMessage(): string
    {
        return 'Wrong username or password.';
    }
}
