<?php

namespace Tests\Integration\Services\Streamer;

use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\PhpStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\Streamer\Adapters\XAccelRedirectStreamerAdapter;
use App\Services\Streamer\Adapters\XSendFileStreamerAdapter;
use App\Services\Streamer\Streamer;
use App\Values\SongStorageTypes;
use Exception;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

use function Tests\test_path;

class StreamerTest extends TestCase
{
    public function testResolveAdapters(): void
    {
        collect(SongStorageTypes::ALL_TYPES)
            ->each(function (?string $type): void {
                /** @var Song $song */
                $song = Song::factory()->make(['storage' => $type]);

                switch ($type) {
                    case SongStorageTypes::S3:
                    case SongStorageTypes::DROPBOX:
                        $this->expectException(KoelPlusRequiredException::class);
                        new Streamer($song);
                        break;

                    case SongStorageTypes::S3_LAMBDA:
                        self::assertInstanceOf(S3CompatibleStreamerAdapter::class, (new Streamer($song))->getAdapter());
                        break;

                    case SongStorageTypes::LOCAL:
                        self::assertInstanceOf(LocalStreamerAdapter::class, (new Streamer($song))->getAdapter());
                        break;

                    default:
                        throw new Exception("Unhandled storage type: $type");
                }
            });
    }

    public function testResolveTranscodingAdapter(): void
    {
        config(['koel.streaming.transcode_flac' => true]);

        File::partialMock()->shouldReceive('mimeType')->andReturn('audio/flac');

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);
        self::assertInstanceOf(TranscodingStreamerAdapter::class, (new Streamer($song))->getAdapter());

        config(['koel.streaming.transcode_flac' => false]);
    }

    public function testForceTranscodingAdapter(): void
    {
        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);

        self::assertInstanceOf(
            TranscodingStreamerAdapter::class,
            (new Streamer($song, null, ['transcode' => true]))->getAdapter()
        );
    }

    /** @return array<mixed> */
    public function provideStreamConfigData(): array
    {
        return [
            PhpStreamerAdapter::class => [null, PhpStreamerAdapter::class],
            XSendFileStreamerAdapter::class => ['x-sendfile', XSendFileStreamerAdapter::class],
            XAccelRedirectStreamerAdapter::class => ['x-accel-redirect', XAccelRedirectStreamerAdapter::class],
        ];
    }

    /** @dataProvider provideStreamConfigData */
    public function testResolveLocalAdapter(?string $config, string $expectedClass): void
    {
        config(['koel.streaming.method' => $config]);

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);

        self::assertInstanceOf($expectedClass, (new Streamer($song))->getAdapter());

        config(['koel.streaming.method' => null]);
    }
}
