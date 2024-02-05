<?php

namespace App\Services\Streamers;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\TranscodingService;
use App\Values\SongStorageTypes;

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
        throw_unless(SongStorageTypes::supported($song->storage), KoelPlusRequiredException::class);

        if ($song->storage === SongStorageTypes::S3 || $song->storage === SongStorageTypes::S3_LAMBDA) {
            return self::makeStreamerFromClass(S3CompatibleStreamer::class, $song);
        }

        if ($song->storage === SongStorageTypes::DROPBOX) {
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
