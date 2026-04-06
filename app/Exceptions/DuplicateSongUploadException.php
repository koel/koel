<?php

namespace App\Exceptions;

final class DuplicateSongUploadException extends SongUploadFailedException
{
    public static function fromFilePath(string $filePath): self
    {
        return new self(sprintf('"%s" already exists in your library.', basename($filePath)));
    }
}
