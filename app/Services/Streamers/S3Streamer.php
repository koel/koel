<?php

namespace App\Services\Streamers;

class S3Streamer extends Streamer implements StreamerInterface
{
    /**
     * Stream the current song through S3.
     * Actually, we only redirect to the S3 object's location.
     */
    public function stream()
    {
        // Get and redirect to the actual presigned-url
        return redirect($this->song->getObjectStoragePublicUrl());
    }
}
