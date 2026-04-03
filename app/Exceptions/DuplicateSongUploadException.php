<?php

namespace App\Exceptions;

final class DuplicateSongUploadException extends SongUploadFailedException
{
    public static function fromFilePath(string $filePath): self
    {
        return new self("$filePath already exists in the user's library.");
    }
}
