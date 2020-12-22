<?php

namespace App\Services\Streamers;

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

class PHPStreamer extends Streamer implements DirectStreamerInterface
{
    public function stream(): void
    {
        try {
            $rangeSet = RangeSet::createFromHeader(get_request_header('Range'));
            $resource = new FileResource($this->song->path);
            (new ResourceServlet($resource))->sendResource($rangeSet);
        } catch (InvalidRangeHeaderException $e) {
            abort(Response::HTTP_BAD_REQUEST);
        } catch (UnsatisfiableRangeException $e) {
            abort(Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE);
        } catch (NonExistentFileException $e) {
            abort(Response::HTTP_NOT_FOUND);
        } catch (UnreadableFileException $e) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (SendFileFailureException $e) {
            abort_unless(headers_sent(), Response::HTTP_INTERNAL_SERVER_ERROR);
            echo "An error occurred while attempting to send the requested resource: {$e->getMessage()}";
        }

        exit;
    }
}
