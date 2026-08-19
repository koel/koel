<?php

namespace Tests\Integration\Services\Streamer\Adapters;

use App\Enums\SongStorageType;
use App\Enums\TranscodeCodec;
use App\Exceptions\TranscodingFailedException;
use App\Models\Song;
use App\Services\Streamer\Adapters\ProgressiveTranscodingStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\Transcoding\ClientConnection;
use App\Services\Transcoding\LocalTranscodingStrategy;
use App\Services\Transcoding\OpusTranscodeCoordinator;
use App\Services\Transcoding\ProgressiveTranscodeSession;
use App\Services\Transcoding\ProgressiveTranscodeSourceResolver;
use App\Services\Transcoding\Transcoder;
use App\Values\ProgressiveTranscodeSource;
use App\Values\RequestedStreamingConfig;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Sleep;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProgressiveTranscodingStreamerAdapterTest extends TestCase
{
    private Song $song;
    private Transcoder&MockInterface $transcoder;
    private ProgressiveTranscodeSourceResolver&MockInterface $sourceResolver;
    private LocalTranscodingStrategy&MockInterface $strategy;
    private TranscodingStreamerAdapter&MockInterface $completedTranscodeAdapter;
    private ProgressiveTranscodingStreamerAdapter $adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->song = Song::factory()->createOne([
            'storage' => SongStorageType::LOCAL,
            'path' => '/music/song.aiff',
            'mime_type' => 'audio/aiff',
        ]);
        $this->transcoder = $this->mock(Transcoder::class);
        $this->sourceResolver = $this->mock(ProgressiveTranscodeSourceResolver::class);
        $this->strategy = $this->mock(LocalTranscodingStrategy::class);
        $this->completedTranscodeAdapter = $this->mock(TranscodingStreamerAdapter::class);
        $this->adapter = new ProgressiveTranscodingStreamerAdapter(
            new ProgressiveTranscodeSession(
                $this->transcoder,
                $this->sourceResolver,
                app(OpusTranscodeCoordinator::class),
                clientConnection: new ClientConnection(),
            ),
            $this->completedTranscodeAdapter,
            defaultBitRate: 256,
            ffmpegPath: PHP_BINARY,
        );
    }

    #[Test]
    public function returnsCompletedCacheThroughExistingAdapterPath(): void
    {
        $this->strategy
            ->expects('getExistingTranscodeLocation')
            ->with($this->song, 256)
            ->andReturn('https://example.com/transcode.weba');
        $this->transcoder->expects('streamProgressively')->never();
        $this->completedTranscodeAdapter
            ->expects('streamTranscodeLocation')
            ->with('https://example.com/transcode.weba')
            ->andReturn(response()->redirectTo('https://example.com/transcode.weba'));

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: 256, progressive: true),
        );

        self::assertTrue($response->isRedirect('https://example.com/transcode.weba'));
    }

    #[Test]
    public function rejectsAMissingFfmpegPathBeforePreparingTheStream(): void
    {
        $adapter = new ProgressiveTranscodingStreamerAdapter(
            new ProgressiveTranscodeSession(
                $this->transcoder,
                $this->sourceResolver,
                app(OpusTranscodeCoordinator::class),
                clientConnection: new ClientConnection(),
            ),
            $this->completedTranscodeAdapter,
            defaultBitRate: 256,
            ffmpegPath: null,
        );
        $this->strategy->expects('getExistingTranscodeLocation')->never();
        $this->sourceResolver->expects('resolve')->never();
        $this->transcoder->expects('streamProgressively')->never();

        try {
            $adapter->stream($this->song, RequestedStreamingConfig::make(bitRate: null, progressive: true));
            self::fail('Expected the missing ffmpeg path to be rejected.');
        } catch (HttpException $e) {
            self::assertSame(500, $e->getStatusCode());
            self::assertSame('ffmpeg not found or not executable.', $e->getMessage());
        }
    }

    #[Test]
    public function resolvesTheSourceBeforeReturningAStreamingResponse(): void
    {
        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver
            ->expects('resolve')
            ->with($this->song)
            ->andThrow(new RuntimeException('source unavailable'));
        $this->transcoder->expects('streamProgressively')->never();

        try {
            $this->adapter->stream($this->song, RequestedStreamingConfig::make(bitRate: null, progressive: true));
            self::fail('Expected source preparation to fail.');
        } catch (RuntimeException $e) {
            self::assertSame('source unavailable', $e->getMessage());
        }

        self::assertSame([], glob(artifact_path('tmp/*.live.webm', ensureDirectoryExists: false)));
        self::assertSame([], glob(artifact_path('tmp/*.weba', ensureDirectoryExists: false)));

        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        $lock->release();
    }

    #[Test]
    public function terminationCleansPreparedResourcesWhenTheResponseIsNeverStreamed(): void
    {
        $temporarySource = artifact_path('tmp/abandoned-source.aiff');
        File::put($temporarySource, 'source');
        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver
            ->expects('resolve')
            ->with($this->song)
            ->andReturn(ProgressiveTranscodeSource::make($temporarySource, temporary: true));
        $this->transcoder->expects('streamProgressively')->never();

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null, progressive: true),
        );

        self::assertInstanceOf(StreamedResponse::class, $response);

        $this->app->terminate();

        self::assertFalse(File::exists($temporarySource));
        self::assertSame([], glob(artifact_path('tmp/*.live.webm', ensureDirectoryExists: false)));
        self::assertSame([], glob(artifact_path('tmp/*.weba', ensureDirectoryExists: false)));

        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        $lock->release();
    }

    #[Test]
    public function servesACachePublishedBeforeLockOwnershipWasAcquired(): void
    {
        $this->strategy
            ->expects('getExistingTranscodeLocation')
            ->with($this->song, 256)
            ->twice()
            ->andReturn(null, 'https://example.com/cache/song.weba');
        $this->sourceResolver->expects('resolve')->never();
        $this->transcoder->expects('streamProgressively')->never();
        $this->completedTranscodeAdapter
            ->expects('streamTranscodeLocation')
            ->with('https://example.com/cache/song.weba')
            ->andReturn(response()->redirectTo('https://example.com/cache/song.weba'));

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null, progressive: true),
        );

        self::assertTrue($response->isRedirect('https://example.com/cache/song.weba'));

        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        $lock->release();
    }

    #[Test]
    public function releasesOwnershipWhenCacheRecheckFails(): void
    {
        $coordinator = $this->mock(OpusTranscodeCoordinator::class);
        $lock = $this->mock(Lock::class);
        $adapter = new ProgressiveTranscodingStreamerAdapter(
            new ProgressiveTranscodeSession(
                $this->transcoder,
                $this->sourceResolver,
                $coordinator,
                clientConnection: new ClientConnection(),
            ),
            $this->completedTranscodeAdapter,
            defaultBitRate: 256,
            ffmpegPath: PHP_BINARY,
        );

        $this->strategy
            ->expects('getExistingTranscodeLocation')
            ->twice()
            ->andReturnUsing(static function (): ?string {
                static $attempt = 0;

                throw_if(++$attempt === 2, new RuntimeException('cache unavailable'));

                return null;
            });
        $coordinator->expects('acquire')->with($this->song, 256)->andReturn($lock);
        $lock->expects('release')->once();
        $this->sourceResolver->expects('resolve')->never();
        $this->transcoder->expects('streamProgressively')->never();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cache unavailable');

        $adapter->stream($this->song, RequestedStreamingConfig::make(bitRate: null, progressive: true));
    }

    #[Test]
    public function servesACachePublishedByTheCurrentLockOwner(): void
    {
        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        Sleep::fake();
        Sleep::whenFakingSleep($lock->release(...));

        $this->strategy
            ->expects('getExistingTranscodeLocation')
            ->with($this->song, 256)
            ->twice()
            ->andReturn(null, 'https://example.com/cache/song.weba');
        $this->sourceResolver->expects('resolve')->never();
        $this->transcoder->expects('streamProgressively')->never();
        $this->completedTranscodeAdapter
            ->expects('streamTranscodeLocation')
            ->with('https://example.com/cache/song.weba')
            ->andReturn(response()->redirectTo('https://example.com/cache/song.weba'));

        try {
            $response = $this->adapter->stream(
                $this->song,
                RequestedStreamingConfig::make(bitRate: null, progressive: true),
            );

            self::assertTrue($response->isRedirect('https://example.com/cache/song.weba'));
            Sleep::assertSleptTimes(1);
        } finally {
            $lock->release();
            Sleep::fake(false);
        }
    }

    #[Test]
    public function streamsFirstChunkBeforeTranscodingCompletesAndPublishesZeroOffsetCache(): void
    {
        $this->strategy->expects('getExistingTranscodeLocation')->with($this->song, 256)->twice()->andReturnNull();
        $this->sourceResolver
            ->expects('resolve')
            ->with($this->song)
            ->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));

        $transcodingComplete = false;
        $firstChunkArrivedBeforeCompletion = false;
        $cachePublished = false;
        $this->transcoder
            ->expects('streamProgressively')
            ->withArgs(static function (string $source, int $bitRate, float $startTime, callable $onAudioChunk) use (
                &$transcodingComplete,
                &$firstChunkArrivedBeforeCompletion,
            ): bool {
                self::assertSame('/music/song.aiff', $source);
                self::assertSame(256, $bitRate);
                self::assertSame(0.0, $startTime);

                $onAudioChunk('webm-header');
                $firstChunkArrivedBeforeCompletion = !$transcodingComplete;
                $onAudioChunk('opus-audio');
                $transcodingComplete = true;

                return true;
            });
        $this->transcoder
            ->expects('finalizeProgressiveTranscode')
            ->withArgs(
                static fn (string $capture, string $indexed): bool => (
                    str_ends_with($capture, '.live.webm') && str_ends_with($indexed, '.weba')
                ),
            );
        $this->strategy
            ->expects('publishCompletedTranscode')
            ->withArgs(
                static fn (Song $song, string $indexed, int $bitRate, TranscodeCodec $codec): bool => (
                    $song->id !== ''
                    && str_ends_with($indexed, '.weba')
                    && $bitRate === 256
                    && $codec === TranscodeCodec::OPUS
                ),
            )
            ->andReturnUsing(static function () use (&$cachePublished): string {
                $cachePublished = true;

                return '/cache/song.weba';
            });

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null, progressive: true),
        );

        self::assertInstanceOf(StreamedResponse::class, $response);
        self::assertSame('audio/webm; codecs=opus', $response->headers->get('Content-Type'));
        self::assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        self::assertSame('no', $response->headers->get('X-Accel-Buffering'));
        self::assertFalse($response->headers->has('Content-Length'));
        self::assertFalse($response->headers->has('Accept-Ranges'));

        $output = $this->captureStreamedOutput($response);

        self::assertSame('webm-headeropus-audio', $output);
        self::assertTrue($firstChunkArrivedBeforeCompletion);
        self::assertFalse($cachePublished);

        $this->app->terminate();
    }

    #[Test]
    public function terminationPublishesACompletedOwnerExactlyOnceBeforeCleanup(): void
    {
        $finalizationCount = 0;
        $publicationCount = 0;
        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver->expects('resolve')->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));
        $this->transcoder
            ->expects('streamProgressively')
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('complete-audio');
            });
        $this->transcoder
            ->expects('finalizeProgressiveTranscode')
            ->once()
            ->andReturnUsing(static function (string $capturePath, string $indexedPath) use (
                &$finalizationCount,
            ): void {
                self::assertTrue(File::exists($capturePath));
                File::put($indexedPath, 'indexed-audio');
                $finalizationCount++;
            });
        $this->strategy
            ->expects('publishCompletedTranscode')
            ->once()
            ->andReturnUsing(static function (
                Song $song,
                string $indexedPath,
                int $bitRate,
                TranscodeCodec $codec,
            ) use (&$publicationCount): string {
                self::assertTrue(File::exists($indexedPath));
                $publicationCount++;

                return '/cache/song.weba';
            });

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null, progressive: true),
        );
        self::assertSame('complete-audio', $this->captureStreamedOutput($response));

        $this->app->terminate();
        $this->app->terminate();

        self::assertSame(1, $finalizationCount);
        self::assertSame(1, $publicationCount);

        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        $lock->release();
    }

    #[Test]
    public function publicationFailureDoesNotPoisonTheCompletedClientStream(): void
    {
        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver->expects('resolve')->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));
        $this->transcoder
            ->expects('streamProgressively')
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('complete-audio');
            });

        $capturePath = null;
        $this->transcoder
            ->expects('finalizeProgressiveTranscode')
            ->andReturnUsing(static function (string $livePath, string $indexedPath) use (&$capturePath): void {
                $capturePath = $livePath;
                File::put($indexedPath, 'indexed-audio');
            });
        $this->strategy->expects('publishCompletedTranscode')->andThrow(new RuntimeException('cache unavailable'));

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null, progressive: true),
        );

        self::assertSame('complete-audio', $this->captureStreamedOutput($response));

        $this->app->terminate();

        self::assertNotNull($capturePath);
        self::assertFalse(File::exists($capturePath));

        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        $lock->release();
    }

    #[Test]
    public function cacheOwnerKeepsIgnoringDisconnectsUntilDeferredPublicationFinishes(): void
    {
        $connection = $this->mock(ClientConnection::class);
        $adapter = new ProgressiveTranscodingStreamerAdapter(
            new ProgressiveTranscodeSession(
                $this->transcoder,
                $this->sourceResolver,
                app(OpusTranscodeCoordinator::class),
                clientConnection: $connection,
            ),
            $this->completedTranscodeAdapter,
            defaultBitRate: 256,
            ffmpegPath: PHP_BINARY,
        );
        $abortBehaviorRestored = false;

        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver->expects('resolve')->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));
        $this->transcoder
            ->expects('streamProgressively')
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('complete-audio');
            });
        $this->transcoder->expects('finalizeProgressiveTranscode');
        $this->strategy->expects('publishCompletedTranscode')->andReturn('/cache/song.weba');
        $connection->expects('keepRunningAfterDisconnect')->andReturnFalse();
        $connection->expects('flushOutput')->once();
        $connection->expects('isDisconnected')->never();
        $connection
            ->expects('restoreAbortBehavior')
            ->with(false)
            ->andReturnUsing(static function () use (&$abortBehaviorRestored): void {
                $abortBehaviorRestored = true;
            });

        $response = $adapter->stream($this->song, RequestedStreamingConfig::make(bitRate: null, progressive: true));

        self::assertSame('complete-audio', $this->captureStreamedOutput($response));
        self::assertFalse($abortBehaviorRestored);

        $this->app->terminate();
    }

    #[Test]
    public function restoresAbortBehaviorWhenDeferredLockReleaseFails(): void
    {
        $connection = $this->mock(ClientConnection::class);
        $coordinator = $this->mock(OpusTranscodeCoordinator::class);
        $lock = $this->mock(Lock::class);
        $adapter = new ProgressiveTranscodingStreamerAdapter(
            new ProgressiveTranscodeSession(
                $this->transcoder,
                $this->sourceResolver,
                $coordinator,
                clientConnection: $connection,
            ),
            $this->completedTranscodeAdapter,
            defaultBitRate: 256,
            ffmpegPath: PHP_BINARY,
        );

        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $coordinator->expects('acquire')->with($this->song, 256)->andReturn($lock);
        $this->sourceResolver->expects('resolve')->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));
        $this->transcoder
            ->expects('streamProgressively')
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('complete-audio');
            });
        $this->transcoder->expects('finalizeProgressiveTranscode');
        $this->strategy->expects('publishCompletedTranscode')->andReturn('/cache/song.weba');
        $connection->expects('keepRunningAfterDisconnect')->andReturnFalse();
        $connection->expects('flushOutput')->once();
        $connection->expects('isDisconnected')->never();
        $connection->expects('restoreAbortBehavior')->with(false)->once();
        $lock->expects('release')->andThrow(new RuntimeException('lock backend unavailable'));

        $response = $adapter->stream($this->song, RequestedStreamingConfig::make(bitRate: null, progressive: true));
        self::assertSame('complete-audio', $this->captureStreamedOutput($response));

        try {
            $this->app->terminate();
            self::fail('Expected deferred lock release to fail.');
        } catch (RuntimeException $e) {
            self::assertSame('lock backend unavailable', $e->getMessage());
        }
    }

    #[Test]
    public function scrubStartsAtOffsetAndNeverPublishesPartialTranscode(): void
    {
        $this->strategy->expects('getExistingTranscodeLocation')->andReturnNull();
        $this->sourceResolver->expects('resolve')->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));
        $this->transcoder
            ->expects('streamProgressively')
            ->with('/music/song.aiff', 192, 173.25, \Mockery::type('callable'))
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('seeked-audio');
            });
        $this->transcoder->expects('finalizeProgressiveTranscode')->never();
        $this->strategy->expects('publishCompletedTranscode')->never();

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: 192, startTime: 173.25, progressive: true),
        );

        self::assertSame('seeked-audio', $this->captureStreamedOutput($response));
    }

    #[Test]
    public function zeroOffsetWaiterBecomesOwnerAfterPreviousProducerFails(): void
    {
        $lock = Cache::lock($this->cacheLockKey(), 60);
        self::assertTrue($lock->get());
        Sleep::fake();
        Sleep::whenFakingSleep($lock->release(...));

        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver->expects('resolve')->andReturn(ProgressiveTranscodeSource::make('/music/song.aiff'));
        $this->transcoder
            ->expects('streamProgressively')
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('new-owner-audio');
            });
        $this->transcoder->expects('finalizeProgressiveTranscode');
        $this->strategy->expects('publishCompletedTranscode')->andReturn('/cache/song.weba');

        try {
            $response = $this->adapter->stream(
                $this->song,
                RequestedStreamingConfig::make(bitRate: null, progressive: true),
            );

            self::assertSame('new-owner-audio', $this->captureStreamedOutput($response));
            Sleep::assertSleptTimes(1);

            $this->app->terminate();
        } finally {
            $lock->release();
            Sleep::fake(false);
        }
    }

    #[Test]
    public function disconnectedSeekCleansUpItsTemporarySource(): void
    {
        $temporarySource = artifact_path('tmp/disconnected-source.aiff');
        File::put($temporarySource, 'source');
        $connection = $this->mock(ClientConnection::class);
        $adapter = new ProgressiveTranscodingStreamerAdapter(
            new ProgressiveTranscodeSession(
                $this->transcoder,
                $this->sourceResolver,
                app(OpusTranscodeCoordinator::class),
                clientConnection: $connection,
            ),
            $this->completedTranscodeAdapter,
            defaultBitRate: 128,
            ffmpegPath: PHP_BINARY,
        );

        $this->strategy->expects('getExistingTranscodeLocation')->andReturnNull();
        $this->sourceResolver
            ->expects('resolve')
            ->andReturn(ProgressiveTranscodeSource::make($temporarySource, temporary: true));
        $this->transcoder
            ->expects('streamProgressively')
            ->andReturnUsing(static function (
                string $source,
                int $bitRate,
                float $startTime,
                callable $onAudioChunk,
            ): void {
                $onAudioChunk('partial-audio');
            });
        $this->transcoder->expects('finalizeProgressiveTranscode')->never();
        $this->strategy->expects('publishCompletedTranscode')->never();
        $connection->expects('keepRunningAfterDisconnect')->andReturnFalse();
        $connection->expects('flushOutput')->once();
        $connection->expects('isDisconnected')->andReturnTrue();
        $connection->expects('restoreAbortBehavior')->with(false)->once();

        $response = $adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: 128, startTime: 30, progressive: true),
        );

        self::assertSame('partial-audio', $this->captureStreamedOutput($response));
        self::assertFalse(File::exists($temporarySource));
    }

    #[Test]
    public function removesPartialFilesAndReleasesLockAfterFailure(): void
    {
        $temporarySource = artifact_path('tmp/source.aiff');
        File::put($temporarySource, 'source');
        $this->strategy->expects('getExistingTranscodeLocation')->twice()->andReturnNull();
        $this->sourceResolver
            ->expects('resolve')
            ->andReturn(ProgressiveTranscodeSource::make($temporarySource, temporary: true));
        $this->transcoder->expects('streamProgressively')->andThrow(new TranscodingFailedException('broken input'));
        $this->transcoder->expects('finalizeProgressiveTranscode')->never();
        $this->strategy->expects('publishCompletedTranscode')->never();

        $response = $this->adapter->stream(
            $this->song,
            RequestedStreamingConfig::make(bitRate: null, progressive: true),
        );

        try {
            $this->captureStreamedOutput($response);
            self::fail('Expected progressive transcode to fail.');
        } catch (TranscodingFailedException) {
            self::assertSame([], glob(artifact_path('tmp/*.live.webm', ensureDirectoryExists: false)));
            self::assertSame([], glob(artifact_path('tmp/*.weba', ensureDirectoryExists: false)));
            self::assertFalse(File::exists($temporarySource));

            $lock = Cache::lock($this->cacheLockKey(), 60);
            self::assertTrue($lock->get());
            $lock->release();
        }
    }

    private function cacheLockKey(int $bitRate = 256): string
    {
        return sprintf('transcode-cache:%s:%d:%s', $this->song->id, $bitRate, TranscodeCodec::OPUS->value);
    }

    private function captureStreamedOutput(StreamedResponse $response): string
    {
        $output = '';
        ob_start(static function (string $chunk) use (&$output): string {
            $output .= $chunk;

            return '';
        });

        try {
            $response->sendContent();
        } finally {
            ob_end_clean();
        }

        return $output;
    }
}
