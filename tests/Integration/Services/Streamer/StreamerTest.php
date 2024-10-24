<?php

namespace Tests\Integration\Services\Streamer;

use App\Enums\SongStorageType;
use App\Exceptions\KoelPlusRequiredException;
use App\Models\Song;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\PhpStreamerAdapter;
use App\Services\Streamer\Adapters\PodcastStreamerAdapter;
use App\Services\Streamer\Adapters\S3CompatibleStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\Streamer\Adapters\XAccelRedirectStreamerAdapter;
use App\Services\Streamer\Adapters\XSendFileStreamerAdapter;
use App\Services\Streamer\Streamer;
use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\test_path;

class StreamerTest extends TestCase
{
    #[Test]
    public function resolveAdapters(): void
    {
        // prevent real HTTP calls from being made e.g. from DropboxStorage
        Http::fake();

        collect(SongStorageType::cases())
            ->each(function (SongStorageType $type): void {
                /** @var Song $song */
                $song = Song::factory()->make(['storage' => $type]);

                switch ($type) {
                    case SongStorageType::S3:
                    case SongStorageType::DROPBOX:
                        $this->expectException(KoelPlusRequiredException::class);
                        new Streamer($song);
                        break;

                    case SongStorageType::S3_LAMBDA:
                        self::assertInstanceOf(S3CompatibleStreamerAdapter::class, (new Streamer($song))->getAdapter());
                        break;

                    case SongStorageType::LOCAL:
                        self::assertInstanceOf(LocalStreamerAdapter::class, (new Streamer($song))->getAdapter());
                        break;

                    default:
                        throw new Exception('Storage type uncovered by tests.');
                }
            });
    }

    #[Test]
    public function resolveTranscodingAdapter(): void
    {
        config(['koel.streaming.transcode_flac' => true]);

        File::partialMock()->shouldReceive('mimeType')->andReturn('audio/flac');

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);
        self::assertInstanceOf(TranscodingStreamerAdapter::class, (new Streamer($song))->getAdapter());

        config(['koel.streaming.transcode_flac' => false]);
    }

    #[Test]
    public function forceTranscodingAdapter(): void
    {
        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);

        self::assertInstanceOf(
            TranscodingStreamerAdapter::class,
            (new Streamer($song, null, ['transcode' => true]))->getAdapter()
        );
    }

    /** @return array<mixed> */
    public static function provideStreamConfigData(): array
    {
        return [
            PhpStreamerAdapter::class => [null, PhpStreamerAdapter::class],
            XSendFileStreamerAdapter::class => ['x-sendfile', XSendFileStreamerAdapter::class],
            XAccelRedirectStreamerAdapter::class => ['x-accel-redirect', XAccelRedirectStreamerAdapter::class],
        ];
    }

    #[DataProvider('provideStreamConfigData')]
    #[Test]
    public function resolveLocalAdapter(?string $config, string $expectedClass): void
    {
        config(['koel.streaming.method' => $config]);

        /** @var Song $song */
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);

        self::assertInstanceOf($expectedClass, (new Streamer($song))->getAdapter());

        config(['koel.streaming.method' => null]);
    }

    #[Test]
    public function resolvePodcastAdapter(): void
    {
        /** @var Song $song */
        $song = Song::factory()->asEpisode()->create();
        $streamer = new Streamer($song);

        self::assertInstanceOf(PodcastStreamerAdapter::class, $streamer->getAdapter());
    }
}
