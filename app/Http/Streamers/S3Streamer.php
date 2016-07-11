<?php

namespace App\Http\Streamers;

use App\Models\Song;

class S3Streamer extends Streamer implements StreamerInterface
{
    public function __construct(Song $song)
    {
        parent::__construct($song);
    }

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
