<?php

namespace App\Services\Streamer\Adapters\Concerns;

use DaveRandom\Resume\FileResource;
use DaveRandom\Resume\InvalidRangeHeaderException;
use DaveRandom\Resume\NonExistentFileException;
use DaveRandom\Resume\RangeSet;
use DaveRandom\Resume\ResourceServlet;
use DaveRandom\Resume\SendFileFailureException;
use DaveRandom\Resume\UnreadableFileException;
use DaveRandom\Resume\UnsatisfiableRangeException;
use Symfony\Component\HttpFoundation\Response;

use function DaveRandom\Resume\get_request_header;

trait StreamsLocalPath
{
    private function streamLocalPath(string $path): string
    {
        try {
            $rangeHeader = get_request_header('Range');

            // On Safari, "Range" header value can be "bytes=0-1" which breaks streaming.
            $rangeHeader = $rangeHeader === 'bytes=0-1' ? 'bytes=0-' : $rangeHeader;

            $rangeSet = RangeSet::createFromHeader($rangeHeader);
            $resource = new FileResource($path, mime_content_type($path));
            (new ResourceServlet($resource))->sendResource($rangeSet);
        } catch (InvalidRangeHeaderException) {
            abort(Response::HTTP_BAD_REQUEST);
        } catch (UnsatisfiableRangeException) {
            abort(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
        } catch (NonExistentFileException) {
            abort(Response::HTTP_NOT_FOUND);
        } catch (UnreadableFileException) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (SendFileFailureException $e) {
            abort_unless(headers_sent(), Response::HTTP_INTERNAL_SERVER_ERROR);
            echo "An error occurred while attempting to send the requested resource: {$e->getMessage()}";
        }

        exit;
    }
}
