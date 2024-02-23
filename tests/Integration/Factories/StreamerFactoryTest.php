<?php

namespace Tests\Integration\Factories;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\Streamers\PhpStreamer;
use App\Services\Streamers\S3CompatibleStreamer;
use App\Services\Streamers\StreamerFactory;
use App\Services\Streamers\TranscodingStreamer;
use App\Services\Streamers\XAccelRedirectStreamer;
use App\Services\Streamers\XSendFileStreamer;
use App\Services\TranscodingService;
use App\Values\SongStorageTypes;
use Exception;
use Mockery;
use phpmock\mockery\PHPMockery;
use Tests\TestCase;

use function Tests\test_path;

class StreamerFactoryTest extends TestCase
{
    private StreamerFactory $streamerFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->streamerFactory = app(StreamerFactory::class);
        PHPMockery::mock('App\Services\Streamers', 'file_exists')->andReturn(true);
    }

    public function testCreateStreamer(): void
    {
        collect(SongStorageTypes::ALL_TYPES)
            ->each(function (?string $type): void {
                switch ($type) {
                    case SongStorageTypes::S3:
                        self::expectException(KoelPlusRequiredException::class);
                        $this->streamerFactory->createStreamer(
                            Song::factory()->make(['path' => "s3://bucket/foo.mp3", 'storage' => $type])
                        );
                        break;

                    case SongStorageTypes::S3_LAMBDA:
                        self::assertInstanceOf(S3CompatibleStreamer::class, $this->streamerFactory->createStreamer(
                            Song::factory()->make(['path' => "s3://bucket/foo.mp3", 'storage' => $type])
                        ));
                        break;

                    case SongStorageTypes::DROPBOX:
                        self::expectException(KoelPlusRequiredException::class);
                        $this->streamerFactory->createStreamer(
                            Song::factory()->make(['path' => "dropbox://foo.mp3", 'storage' => $type])
                        );
                        break;

                    case SongStorageTypes::LOCAL:
                        self::assertInstanceOf(PhpStreamer::class, $this->streamerFactory->createStreamer(
                            Song::factory()->make(['path' => test_path('songs/blank.mp3'), 'storage' => $type])
                        ));
                        break;

                    default:
                        throw new Exception("Unhandled storage type: $type");
                }
            });
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
