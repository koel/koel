<?php

namespace App\Factories;

use App\Models\Song;
use App\Services\Streamers\DirectStreamerInterface;
use App\Services\Streamers\ObjectStorageStreamerInterface;
use App\Services\Streamers\StreamerInterface;
use App\Services\Streamers\TranscodingStreamerInterface;

class StreamerFactory
{
    private $directStreamer;
    private $transcodingStreamer;
    private $objectStorageStreamer;

    public function __construct(
        DirectStreamerInterface $directStreamer,
        TranscodingStreamerInterface $transcodingStreamer,
        ObjectStorageStreamerInterface $objectStorageStreamer
    )
    {
        $this->directStreamer = $directStreamer;
        $this->transcodingStreamer = $transcodingStreamer;
        $this->objectStorageStreamer = $objectStorageStreamer;
    }

    /**
     * @param Song         $song
     *
     * @param boolean|null $transcode
     * @param int|null     $bitRate
     * @param int          $startTime
     *
     * @return StreamerInterface
     */
    public function createStreamer(Song $song, $transcode = null, $bitRate = null, $startTime = 0)
    {
        if ($song->s3_params) {
            $this->objectStorageStreamer->setSong($song);

            return $this->objectStorageStreamer;
        }

        // If `transcode` parameter isn't passed, the default is to only transcode FLAC.
        if ($transcode === null && ends_with(mime_content_type($song->path), 'flac')) {
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
