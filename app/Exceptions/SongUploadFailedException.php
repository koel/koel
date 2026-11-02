<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class SongUploadFailedException extends RuntimeException implements ShouldntReport
{
    public const string GENERIC_USER_FACING_MESSAGE = 'The file could not be processed.';

    private static function fromThrowable(Throwable $e): self
    {
        report($e);

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

    public function getUserFacingMessage(): string
    {
        $cause = $this->getPrevious();

        return !$cause || $cause instanceof UnreadableMediaFileException
            ? $this->getMessage()
            : self::GENERIC_USER_FACING_MESSAGE;
    }

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getUserFacingMessage()], Response::HTTP_BAD_REQUEST);
    }
}
