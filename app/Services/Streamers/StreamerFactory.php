<?php

namespace App\Services\Streamers;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
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
    ): Streamer {
        throw_unless($song->storage_metadata->supported(), KoelPlusRequiredException::class);

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

        return self::makeStreamerFromClass(LocalStreamer::class, $song);
    }

    private static function makeStreamerFromClass(string $class, Song $song): Streamer
    {
        return tap(app($class), static fn (Streamer $streamer) => $streamer->setSong($song));
    }
}
