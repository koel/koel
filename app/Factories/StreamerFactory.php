<?php

namespace App\Factories;

use App\Models\Song;
use App\Services\Streamers\DirectStreamerInterface;
use App\Services\Streamers\ObjectStorageStreamerInterface;
use App\Services\Streamers\StreamerInterface;
use App\Services\Streamers\TranscodingStreamerInterface;
use App\Services\TranscodingService;

class StreamerFactory
{
    public function __construct(
        private DirectStreamerInterface $directStreamer,
        private TranscodingStreamerInterface $transcodingStreamer,
        private ObjectStorageStreamerInterface $objectStorageStreamer,
        private TranscodingService $transcodingService
    ) {
    }

    public function createStreamer(
        Song $song,
        ?bool $transcode = null,
        ?int $bitRate = null,
        float $startTime = 0.0
    ): StreamerInterface {
        if ($song->s3_params) {
            $this->objectStorageStreamer->setSong($song);

            return $this->objectStorageStreamer;
        }

        if ($transcode === null && $this->transcodingService->songShouldBeTranscoded($song)) {
            $transcode = true;
        }

        if ($transcode) {
            $this->transcodingStreamer->setSong($song);
            $this->transcodingStreamer->setBitRate($bitRate ?: config('koel.streaming.bitrate'));
            $this->transcodingStreamer->setStartTime($startTime);

            return $this->transcodingStreamer;
        }

        $this->directStreamer->setSong($song);

        return $this->directStreamer;
    }
}
