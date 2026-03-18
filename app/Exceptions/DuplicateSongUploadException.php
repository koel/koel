<?php

namespace App\Exceptions;

final class DuplicateSongUploadException extends SongUploadFailedException
{
    public static function fromFileName(string $fileName): self
    {
        return new self("$fileName already exists in the user's library.");
    }
}
