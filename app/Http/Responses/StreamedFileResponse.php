<?php

namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class StreamedFileResponse extends BinaryFileResponse
{
    public function prepare(Request $request): static
    {
        parent::prepare($request);

        // A 416 carries no body, but the parent leaves the file's own length behind in the header.
        if ($this->getStatusCode() === Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE) {
            $this->headers->set('Content-Length', '0');
        }

        return $this;
    }
}
