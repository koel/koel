<?php

namespace App\Services\Streamers;

use App\Services\S3Service;

class S3Streamer extends Streamer implements ObjectStorageStreamerInterface
{
    private $s3Service;

    public function __construct(S3Service $s3Service)
    {
        parent::__construct();
        $this->s3Service = $s3Service;
    }

    /**
     * Stream the current song through S3.
     * Actually, we just redirect the request to the S3 object's location.
     */
    public function stream()
    {
        // Get and redirect to the actual presigned-url
        return redirect($this->s3Service->getSongPublicUrl($this->song));
    }
}
