<?php

namespace App\Services\Streamers;

interface TranscodingStreamerInterface extends StreamerInterface
{
    public function setBitRate(int $bitRate): void;

    public function setStartTime(float $startTime): void;
}
