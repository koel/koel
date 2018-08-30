<?php

namespace App\Services\Streamers;

use DaveRandom\Resume\FileResource;
use DaveRandom\Resume\InvalidRangeHeaderException;
use DaveRandom\Resume\NonExistentFileException;
use DaveRandom\Resume\RangeSet;
use DaveRandom\Resume\Resource;
use DaveRandom\Resume\ResourceServlet;
use DaveRandom\Resume\SendFileFailureException;
use DaveRandom\Resume\UnreadableFileException;
use DaveRandom\Resume\UnsatisfiableRangeException;
use function DaveRandom\Resume\get_request_header;

class PHPStreamer extends Streamer implements DirectStreamerInterface
{
    public function stream()
    {
        try {
            $rangeSet = RangeSet::createFromHeader(get_request_header('Range'));
            /** @var Resource $resource */
            $resource = new FileResource($this->song->path, 'application/octet-stream');
            (new ResourceServlet($resource))->sendResource($rangeSet);
        } catch (InvalidRangeHeaderException $e) {
            abort(400);
        } catch (UnsatisfiableRangeException $e) {
            abort(416);
        } catch (NonExistentFileException $e) {
            abort(404);
        } catch (UnreadableFileException $e) {
            abort(500);
        } catch (SendFileFailureException $e) {
            abort_unless(headers_sent(), 500);
            echo "An error occurred while attempting to send the requested resource: {$e->getMessage()}";
        }

        exit;
    }
}
