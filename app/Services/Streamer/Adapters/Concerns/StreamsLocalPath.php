<?php

namespace App\Services\Streamer\Adapters\Concerns;

use App\Http\Responses\StreamedFileResponse;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

trait StreamsLocalPath
{
    private static function streamLocalPath(string $path): StreamedFileResponse
    {
        abort_unless(File::isReadable($path), Response::HTTP_NOT_FOUND);

        $response = new StreamedFileResponse($path, headers: ['Content-Type' => File::mimeType($path)]);
        $response->setContentDisposition(HeaderUtils::DISPOSITION_INLINE, basename($path));

        return $response;
    }
}
