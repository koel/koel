<?php

namespace App\Exceptions\Subsonic;

use App\Exceptions\Contracts\SubsonicThrowable;
use InvalidArgumentException;

class UnsupportedAlbumListTypeException extends InvalidArgumentException implements SubsonicThrowable
{
    public static function create(string $type): self
    {
        return new self("Unsupported album list type: $type");
    }

    public function getSubsonicErrorCode(): int
    {
        return 0;
    }

    public function getSubsonicErrorMessage(): string
    {
        return $this->getMessage();
    }
}
