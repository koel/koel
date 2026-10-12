<?php

namespace App\Exceptions;

use App\Http\Resources\DuplicateUploadResource;
use App\Models\DuplicateUpload;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RuntimeException;

final class DuplicateSongUploadException extends RuntimeException implements ShouldntReport
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

    public function render(): JsonResponse
    {
        return response()->json(new DuplicateUploadResource($this->duplicateUpload), Response::HTTP_CONFLICT);
    }
}
