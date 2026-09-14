<?php

namespace App\Services\Streamer\Adapters\Concerns;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;

trait StreamsLocalPath
{
    private function streamLocalPath(string $path): BinaryFileResponse
    {
        abort_unless(File::isReadable($path), Response::HTTP_NOT_FOUND);

        $response = response()->file($path, ['Content-Type' => File::mimeType($path)]);
        $response->setContentDisposition(HeaderUtils::DISPOSITION_INLINE, basename($path));

        return $response;
    }
}
