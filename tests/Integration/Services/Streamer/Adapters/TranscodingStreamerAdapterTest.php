<?php

namespace Tests\Integration\Services\Streamer\Adapters;

use App\Enums\SongStorageType;
use App\Models\Song;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Values\RequestedStreamingConfig;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TranscodingStreamerAdapterTest extends TestCase
{
    private Song $song;

    public function setUp(): void
    {
        parent::setUp();

        config([
            'koel.streaming.bitrate' => 128,
            'koel.streaming.ffmpeg_path' => PHP_BINARY,
        ]);

        $this->song = Song::factory()->make(['storage' => SongStorageType::LOCAL]);
    }

    #[Test]
    public function usesDefaultBitRateWithoutConfig(): void
    {
        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($this->song, 128)
            ->andReturn('https://example.com/transcode.m4a');

        $response = app(TranscodingStreamerAdapter::class)->stream($this->song);

        self::assertTrue($response->isRedirect('https://example.com/transcode.m4a'));
    }

    #[Test]
    public function usesRequestedBitRate(): void
    {
        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($this->song, 256)
            ->andReturn('https://example.com/transcode.weba');

        $response = app(TranscodingStreamerAdapter::class)->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: 256),
        );

        self::assertTrue($response->isRedirect('https://example.com/transcode.weba'));
    }

    #[Test]
    public function usesDefaultBitRateWhenNoneRequested(): void
    {
        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($this->song, 128)
            ->andReturn('https://example.com/transcode.weba');

        $response = app(TranscodingStreamerAdapter::class)->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null),
        );

        self::assertTrue($response->isRedirect('https://example.com/transcode.weba'));
    }
}
