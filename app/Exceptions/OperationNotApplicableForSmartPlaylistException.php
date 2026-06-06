<?php

namespace App\Exceptions;

use App\Exceptions\Contracts\SubsonicThrowable;
use DomainException;

class OperationNotApplicableForSmartPlaylistException extends DomainException implements SubsonicThrowable
{
    public function getSubsonicErrorCode(): int
    {
        return 0;
    }

    public function getSubsonicErrorMessage(): string
    {
        return 'Operation is not applicable to smart playlists.';
    }
}
