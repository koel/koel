<?php

namespace App\Exceptions;

use App\Models\DuplicateUpload;
use RuntimeException;

final class DuplicateSongUploadException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly DuplicateUpload $duplicateUpload,
    ) {
        parent::__construct($message);
    }

    public static function create(string $filePath, DuplicateUpload $duplicateUpload): self
    {
        return new self(sprintf('"%s" already exists in your library.', basename($filePath)), $duplicateUpload);
    }
}
