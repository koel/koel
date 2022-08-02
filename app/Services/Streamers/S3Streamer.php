<?php

namespace App\Services\Streamers;

use App\Services\S3Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

class S3Streamer extends Streamer implements ObjectStorageStreamerInterface
{
    public function __construct(private S3Service $s3Service)
    {
        parent::__construct();
    }

    /**
     * Stream the current song through S3.
     * Actually, we just redirect the request to the S3 object's location.
     *
     * @return Redirector|RedirectResponse
     *
     */
    public function stream() // @phpcs:ignore
    {
        // Get and redirect to the actual presigned-url
        return redirect($this->s3Service->getSongPublicUrl($this->song));
    }
}
