<?php

namespace Tests\Integration\Factories;

use App\Factories\StreamerFactory;
use App\Models\Song;
use App\Services\Streamers\PhpStreamer;
use App\Services\Streamers\S3Streamer;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use App\Services\TranscodingService;
use Mockery;
use phpmock\mockery\PHPMockery;
use Tests\TestCase;

class StreamerFactoryTest extends TestCase
{
    private StreamerFactory $streamerFactory;

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
        $this->swap(TranscodingService::class, Mockery::mock(TranscodingService::class, [
            'songShouldBeTranscoded' => true,
        ]));

        /** @var StreamerFactory $streamerFactory */
        $streamerFactory = app(StreamerFactory::class);

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);
        self::assertInstanceOf(TranscodingStreamer::class, $streamerFactory->createStreamer($song));
    }

    public function testCreateTranscodingStreamerIfForced(): void
    {
        $this->swap(TranscodingService::class, Mockery::mock(TranscodingService::class, [
            'songShouldBeTranscoded' => false,
        ]));

        /** @var StreamerFactory $streamerFactory */
        $streamerFactory = app(StreamerFactory::class);

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);
        self::assertInstanceOf(TranscodingStreamer::class, $streamerFactory->createStreamer($song, true));
    }

    /** @return array<mixed> */
    public function provideStreamingConfigData(): array
    {
        return [
            [null, PhpStreamer::class],
            ['x-sendfile', XSendFileStreamer::class],
            ['x-accel-redirect', XAccelRedirectStreamer::class],
        ];
    }

    /**
     * @dataProvider provideStreamingConfigData
     *
     * @param string|null $config
     * @param string $expectedClass
     */
    public function testCreatePhpStreamer($config, $expectedClass): void
    {
        $this->swap(TranscodingService::class, Mockery::mock(TranscodingService::class, [
            'songShouldBeTranscoded' => false,
        ]));

        config(['koel.streaming.method' => $config]);

        /** @var StreamerFactory $streamerFactory */
        $streamerFactory = app(StreamerFactory::class);

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);
        self::assertInstanceOf($expectedClass, $streamerFactory->createStreamer($song));
    }
}
