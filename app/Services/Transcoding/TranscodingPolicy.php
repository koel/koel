<?php

namespace App\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Models\Song;
use App\Values\RequestedStreamingConfig;
use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Foundation\Application;

class TranscodingPolicy
{
    /** @param list<string> $requiredMimeTypes */
    public function __construct(
        private readonly Transcoder $transcoder,
        private readonly Application $application,
        #[Config('koel.streaming.progressive')]
        private readonly bool $progressiveEnabled,
        #[Config('koel.streaming.ffmpeg_path')]
        private readonly ?string $ffmpegPath,
        #[Config('koel.streaming.transcode_flac')]
        private readonly bool $transcodeFlac,
        #[Config('koel.streaming.transcode_required_mime_types')]
        private readonly array $requiredMimeTypes,
    ) {}

    public function requiresTranscoding(Song $song): bool
    {
        if ($song->isEpisode() || !$this->hasValidFfmpegInstallation()) {
            return false;
        }

        if ($song->isFlac() && $this->transcodeFlac) {
            return true;
        }

        return in_array($song->mime_type, $this->requiredMimeTypes, true);
    }

    public function requiresCompatibilityTranscoding(Song $song): bool
    {
        if ($song->isEpisode() || !$this->hasValidFfmpegInstallation()) {
            return false;
        }

        if ($song->isFlac()) {
            return false;
        }

        return in_array($song->mime_type, $this->requiredMimeTypes, true);
    }

    public function shouldStreamProgressively(Song $song, ?RequestedStreamingConfig $config): bool
    {
        return (
            $this->progressiveEnabled
            && $config?->progressive
            && !$config->transcode
            && ($this->application->runningUnitTests() || $this->preferredCodecIsOpus())
            && $this->requiresCompatibilityTranscoding($song)
        );
    }

    public function supportsProgressiveTranscoding(): bool
    {
        return $this->progressiveEnabled && $this->preferredCodecIsOpus();
    }

    private function preferredCodecIsOpus(): bool
    {
        return $this->transcoder->preferredCodec() === TranscodeCodec::OPUS;
    }

    private function hasValidFfmpegInstallation(): bool
    {
        return $this->application->runningUnitTests() || is_executable((string) $this->ffmpegPath);
    }
}
