<?php

namespace Tests\Integration\Factories;

use App\Factories\StreamerFactory;
use App\Models\Song;
use App\Services\Streamers\PHPStreamer;
use App\Services\Streamers\S3Streamer;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use App\Services\TranscodingService;
use phpmock\mockery\PHPMockery;
use Tests\TestCase;

class StreamerFactoryTest extends TestCase
{
    /** @var StreamerFactory */
    private $streamerFactory;

    public function setUp(): void
    {
        parent::setUp();
        $this->streamerFactory = app(StreamerFactory::class);
        PHPMockery::mock('App\Services\Streamers', 'file_exists')->andReturn(true);
    }

    public function testCreateS3Streamer(): void
    {
        /** @var Song $song */
        $song = Song::factory()->make(['path' => 's3://bucket/foo.mp3']);

        self::assertInstanceOf(S3Streamer::class, $this->streamerFactory->createStreamer($song));
    }

    public function testCreateTranscodingStreamerIfSupported(): void
    {
        static::mockIocDependency(TranscodingService::class, [
            'songShouldBeTranscoded' => true,
        ]);

        /** @var StreamerFactory $streamerFactory */
        $streamerFactory = app(StreamerFactory::class);

        /** @var Song $song */
        $song = Song::factory()->make();
        self::assertInstanceOf(TranscodingStreamer::class, $streamerFactory->createStreamer($song, null));
    }

    public function testCreateTranscodingStreamerIfForced(): void
    {
        static::mockIocDependency(TranscodingService::class, [
            'songShouldBeTranscoded' => false,
        ]);

        /** @var StreamerFactory $streamerFactory */
        $streamerFactory = app(StreamerFactory::class);

        $song = Song::factory()->make();
        self::assertInstanceOf(TranscodingStreamer::class, $streamerFactory->createStreamer($song, true));
    }

    public function provideStreamingConfigData(): array
    {
        return [
            [null, PHPStreamer::class],
            ['x-sendfile', XSendFileStreamer::class],
            ['x-accel-redirect', XAccelRedirectStreamer::class],
        ];
    }

    /**
     * @dataProvider provideStreamingConfigData
     *
     * @param string|null $config
     * @param string      $expectedClass
     */
    public function testCreatePHPStreamer($config, $expectedClass): void
    {
        static::mockIocDependency(TranscodingService::class, [
            'songShouldBeTranscoded' => false,
        ]);

        config(['koel.streaming.method' => $config]);

        /** @var StreamerFactory $streamerFactory */
        $streamerFactory = app(StreamerFactory::class);

        $song = Song::factory()->make();
        self::assertInstanceOf($expectedClass, $streamerFactory->createStreamer($song));
    }
}
