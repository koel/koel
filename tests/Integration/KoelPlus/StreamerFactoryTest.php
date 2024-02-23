<?php

namespace Tests\Integration\KoelPlus;

use App\Models\Song;
use App\Services\Streamers\PhpStreamer;
use App\Services\Streamers\S3CompatibleStreamer;
use App\Services\Streamers\StreamerFactory;
use App\Values\SongStorageTypes;
use Exception;
use phpmock\mockery\PHPMockery;
use Tests\PlusTestCase;

use function Tests\test_path;

class StreamerFactoryTest extends PlusTestCase
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
                    case SongStorageTypes::S3_LAMBDA:
                        self::assertInstanceOf(S3CompatibleStreamer::class, $this->streamerFactory->createStreamer(
                            Song::factory()->make(['path' => "s3://bucket/foo.mp3", 'storage' => $type])
                        ));
                        break;

                    case SongStorageTypes::DROPBOX:
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
}
