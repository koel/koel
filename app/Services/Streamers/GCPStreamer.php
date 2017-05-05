<?php

namespace App\Services\Streamers;

class GCPStreamer extends Streamer implements StreamerInterface
{
    /**
     * Stream the current song through .
     * Actually, we only redirect to the  object's location.
     */
    public function stream()
    {
        return redirect($this->song->getGcpObjectStoragePublicUrl());
    }
}
