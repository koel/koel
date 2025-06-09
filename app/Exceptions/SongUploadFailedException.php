<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class SongUploadFailedException extends RuntimeException
{
    public static function fromThrowable(Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }

    public static function fromErrorMessage(?string $error): self
    {
        return new self($error ?? 'An unknown error occurred while uploading the song.');
    }
}
