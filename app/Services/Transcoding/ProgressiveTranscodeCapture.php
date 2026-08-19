<?php

namespace App\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Helpers\Ulid;
use App\Models\Song;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ProgressiveTranscodeCapture
{
    private ?string $capturePath = null;
    private ?string $indexedPath = null;

    /** @var resource|null */
    private mixed $stream = null;

    public function __construct(
        private readonly Transcoder $transcoder,
    ) {}

    public function open(bool $enabled): void
    {
        if (!$enabled) {
            return;
        }

        $this->capturePath = artifact_path(sprintf('tmp/%s.live.webm', Ulid::generate()));
        $this->indexedPath = artifact_path(sprintf('tmp/%s.weba', Ulid::generate()));
        $this->stream = fopen($this->capturePath, 'wb');

        throw_unless(
            is_resource($this->stream),
            RuntimeException::class,
            'Failed to open progressive transcode capture.',
        );
    }

    public function write(string $chunk): void
    {
        if (!is_resource($this->stream)) {
            return;
        }

        $bytesWritten = 0;
        $chunkLength = strlen($chunk);

        while ($bytesWritten < $chunkLength) {
            $written = fwrite($this->stream, substr($chunk, $bytesWritten));
            throw_if($written === false || $written === 0, RuntimeException::class, 'Failed to capture transcode.');
            $bytesWritten += $written;
        }
    }

    public function publish(TranscodingStrategy $strategy, Song $song, int $bitRate): void
    {
        if (!$this->capturePath || !$this->indexedPath) {
            return;
        }

        $this->finishWriting();
        $this->transcoder->finalizeProgressiveTranscode($this->capturePath, $this->indexedPath);
        $strategy->publishCompletedTranscode($song, $this->indexedPath, $bitRate, TranscodeCodec::OPUS);
    }

    public function finishWriting(): void
    {
        $this->close();
    }

    public function cleanup(): void
    {
        $this->close();
        File::delete(array_filter([$this->capturePath, $this->indexedPath]));
    }

    private function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }

        $this->stream = null;
    }
}
