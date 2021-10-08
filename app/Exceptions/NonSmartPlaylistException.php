<?php

namespace App\Exceptions;

use App\Models\Playlist;
use Exception;
use Throwable;

class NonSmartPlaylistException extends Exception
{
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function create(Playlist $playlist): self
    {
        return new static($playlist->name . ' is not a smart playlist.');
    }
}
