<?php

namespace App\Services\Streamers;

interface TranscodingStreamerInterface extends StreamerInterface
{
    public function setBitRate($bitRate);

    public function setStartTime($startTime);
}
