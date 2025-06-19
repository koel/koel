<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class SongUploadFailedException extends RuntimeException
{
    private static function fromThrowable(Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }

    private static function fromErrorMessage(?string $error): self
    {
        return new self($error ?? 'An unknown error occurred while uploading the song.');
    }

    public static function make(Throwable|string $error): self
    {
        if ($error instanceof Throwable) {
            return self::fromThrowable($error);
        }

        return self::fromErrorMessage($error);
    }
}
