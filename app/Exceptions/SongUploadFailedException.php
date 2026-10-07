<?php

namespace App\Exceptions;

use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class SongUploadFailedException extends RuntimeException implements ShouldntReport
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

    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], Response::HTTP_BAD_REQUEST);
    }
}
