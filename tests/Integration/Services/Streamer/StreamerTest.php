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
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Values\RequestedStreamingConfig;
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
        // prevent real HTTP calls from being made, e.g., from DropboxStorage
        Http::fake();

        collect(SongStorageType::cases())->each(function (SongStorageType $type): void {
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
                    self::fail("Storage type not covered by tests: $type->value");
            }
        });
    }

    #[Test]
    public function doNotUseTranscodingAdapterToPlayFlacIfConfiguredSo(): void
    {
        $backup = config('koel.streaming.transcode_flac');
        config(['koel.streaming.transcode_flac' => false]);
        $song = Song::factory()->createOne([
            'storage' => SongStorageType::LOCAL,
            'path' => '/tmp/test.flac',
            'mime_type' => 'audio/flac',
        ]);

        $streamer = new Streamer($song, null);

        self::assertInstanceOf(LocalStreamerAdapter::class, $streamer->getAdapter());

        config(['koel.streaming.transcode_flac' => $backup]);
    }

    #[Test]
    public function useTranscodingAdapterToPlayFlacIfConfiguredSo(): void
    {
        $song = Song::factory()->createOne(['storage' => SongStorageType::LOCAL]);

        $streamer = new Streamer($song, null, RequestedStreamingConfig::make(transcode: true));

        self::assertInstanceOf(TranscodingStreamerAdapter::class, $streamer->getAdapter());
    }

    #[Test]
    public function transcodeFlacWhenConfigured(): void
    {
        config([
            'koel.streaming.bitrate' => 128,
            'koel.streaming.ffmpeg_path' => PHP_BINARY,
            'koel.streaming.transcode_flac' => true,
        ]);
        $song = Song::factory()->createOne([
            'storage' => SongStorageType::LOCAL,
            'path' => '/tmp/test.flac',
            'mime_type' => 'audio/flac',
        ]);

        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($song, 128)
            ->andReturn('https://example.com/transcode.m4a');

        $response = (new Streamer($song, config: RequestedStreamingConfig::make()))->stream();

        self::assertTrue($response->isRedirect('https://example.com/transcode.m4a'));
    }

    #[Test]
    public function useTranscodingAdapterIfSongMimeTypeRequiresTranscoding(): void
    {
        $backupConfig = config('koel.streaming.transcode_required_mime_types');
        config(['koel.streaming.transcode_required_mime_types' => ['audio/aif']]);
        $song = Song::factory()->createOne([
            'storage' => SongStorageType::LOCAL,
            'path' => '/tmp/test.aiff',
            'mime_type' => 'audio/aif',
        ]);

        $streamer = new Streamer($song, null);

        self::assertInstanceOf(TranscodingStreamerAdapter::class, $streamer->getAdapter());

        config(['koel.streaming.transcode_required_mime_types' => $backupConfig]);
    }

    #[Test]
    public function passRequestedBitRateForForcedTranscoding(): void
    {
        config([
            'koel.streaming.ffmpeg_path' => PHP_BINARY,
        ]);
        $song = Song::factory()->createOne([
            'storage' => SongStorageType::LOCAL,
            'path' => '/tmp/test.aiff',
            'mime_type' => 'audio/aiff',
        ]);

        $this
            ->mock(LocalTranscodingStrategy::class)
            ->expects('getTranscodeLocation')
            ->with($song, 64)
            ->andReturn('https://example.com/transcode.m4a');

        $response = (new Streamer($song, config: RequestedStreamingConfig::make(
            transcode: true,
            bitRate: 64,
        )))->stream();

        self::assertTrue($response->isRedirect('https://example.com/transcode.m4a'));
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
        $song = Song::factory()->make(['path' => test_path('songs/blank.mp3')]);

        self::assertInstanceOf($expectedClass, (new Streamer($song))->getAdapter());

        config(['koel.streaming.method' => null]);
    }

    #[Test]
    public function resolvePodcastAdapter(): void
    {
        $song = Song::factory()->asEpisode()->createOne();
        $streamer = new Streamer($song);

        self::assertInstanceOf(PodcastStreamerAdapter::class, $streamer->getAdapter());
    }
}
