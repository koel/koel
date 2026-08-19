<?php

namespace App\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Exceptions\TranscodingFailedException;
use App\Models\Song;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class OpusTranscodeCoordinator
{
    private const int PIPELINE_PHASE_COUNT = 4;

    private const int FFMPEG_PHASE_COUNT = 2;

    private const int GRACE_SECONDS = 60;

    private const int UNLIMITED_LOCK_DURATION_SECONDS = 86_400;

    private const int UNLIMITED_WAIT_DURATION_SECONDS = 3_600;

    public function __construct(
        #[Config('koel.streaming.transcode_timeout')]
        private readonly int $transcodeTimeout,
    ) {}

    public function acquire(Song $song, int $bitRate): Lock
    {
        $lock = Cache::lock($this->key($song, $bitRate), $this->lockDuration());

        try {
            $lock->block($this->waitDuration());
        } catch (LockTimeoutException) {
            throw new TranscodingFailedException('timed out waiting for Opus cache production.');
        }

        return $lock;
    }

    /**
     * @template TResult
     * @param Closure(): TResult $operation
     * @return TResult
     */
    public function runExclusively(Song $song, int $bitRate, Closure $operation): mixed
    {
        $lock = $this->acquire($song, $bitRate);

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }

    private function key(Song $song, int $bitRate): string
    {
        return sprintf('transcode-cache:%s:%d:%s', $song->id, $bitRate, TranscodeCodec::OPUS->value);
    }

    private function lockDuration(): int
    {
        return $this->transcodeTimeout > 0
            ? ($this->transcodeTimeout * self::PIPELINE_PHASE_COUNT) + self::GRACE_SECONDS
            : self::UNLIMITED_LOCK_DURATION_SECONDS;
    }

    private function waitDuration(): int
    {
        return $this->transcodeTimeout > 0
            ? ($this->transcodeTimeout * self::FFMPEG_PHASE_COUNT) + self::GRACE_SECONDS
            : self::UNLIMITED_WAIT_DURATION_SECONDS;
    }
}
