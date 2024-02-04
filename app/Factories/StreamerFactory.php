<?php

namespace App\Factories;

use App\Models\Song;
use App\Services\Streamers\LocalStreamerInterface;
use App\Services\Streamers\S3CompatibleStreamer;
use App\Services\Streamers\StreamerInterface;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\TranscodingService;
use App\Values\SongStorageMetadata\S3CompatibleMetadata;

class StreamerFactory
{
    public function __construct(private TranscodingService $transcodingService)
    {
    }

    public function createStreamer(
        Song $song,
        ?bool $transcode = null,
        ?int $bitRate = null,
        float $startTime = 0.0
    ): StreamerInterface {
        if ($song->storage_metadata instanceof S3CompatibleMetadata) {
            return tap(
                app(S3CompatibleStreamer::class),
                static fn (S3CompatibleStreamer $streamer) => $streamer->setSong($song)
            );
        }

        $transcode ??= $this->transcodingService->songShouldBeTranscoded($song);

        if ($transcode) {
            /** @var TranscodingStreamer $streamer */
            $streamer = app(TranscodingStreamer::class);
            $streamer->setSong($song);
            $streamer->setBitRate($bitRate ?: config('koel.streaming.bitrate'));
            $streamer->setStartTime($startTime);

            return $streamer;
        }

        return tap(
            app(LocalStreamerInterface::class),
            static fn (LocalStreamerInterface $streamer) => $streamer->setSong($song)
        );
    }
}
