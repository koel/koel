<?php

namespace App\Factories;

use App\Models\Song;
use App\Services\Streamers\DropboxStreamer;
use App\Services\Streamers\LocalStreamerInterface;
use App\Services\Streamers\S3CompatibleStreamer;
use App\Services\Streamers\StreamerInterface;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\TranscodingService;
use App\Values\SongStorageMetadata\DropboxMetadata;
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
            return self::makeStreamerFromClass(S3CompatibleStreamer::class, $song);
        }

        if ($song->storage_metadata instanceof DropboxMetadata) {
            return self::makeStreamerFromClass(DropboxStreamer::class, $song);
        }

        $transcode ??= $this->transcodingService->songShouldBeTranscoded($song);

        if ($transcode) {
            /** @var TranscodingStreamer $streamer */
            $streamer = self::makeStreamerFromClass(TranscodingStreamer::class, $song);
            $streamer->setBitRate($bitRate ?: config('koel.streaming.bitrate'));
            $streamer->setStartTime($startTime);

            return $streamer;
        }

        return self::makeStreamerFromClass(LocalStreamerInterface::class, $song);
    }

    private static function makeStreamerFromClass(string $class, Song $song): StreamerInterface
    {
        return tap(app($class), static fn (StreamerInterface $streamer) => $streamer->setSong($song));
    }
}
