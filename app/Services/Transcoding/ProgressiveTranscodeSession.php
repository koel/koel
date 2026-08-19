<?php

namespace App\Services\Transcoding;

use App\Exceptions\ClientDisconnectedException;
use App\Models\Song;
use App\Values\ProgressiveTranscodeSource;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Throwable;

class ProgressiveTranscodeSession
{
    public function __construct(
        private readonly Transcoder $transcoder,
        private readonly ProgressiveTranscodeSourceResolver $sourceResolver,
        private readonly OpusTranscodeCoordinator $coordinator,
        private readonly ClientConnection $clientConnection,
    ) {}

    public function prepare(Song $song, int $bitRate, float $startTime): string|Closure
    {
        $strategy = TranscodeStrategyFactory::make($song->storage);
        $completedLocation = $strategy->getExistingTranscodeLocation($song, $bitRate);

        if ($completedLocation) {
            return $completedLocation;
        }

        $lock = $startTime === 0.0 ? $this->coordinator->acquire($song, $bitRate) : null;
        $capture = new ProgressiveTranscodeCapture($this->transcoder);
        $lifecycle = new ProgressiveTranscodeLifecycle($capture, $lock, $this->clientConnection);
        app()->terminating($lifecycle->terminate(...));

        try {
            if ($lock) {
                $completedLocation = $strategy->getExistingTranscodeLocation($song, $bitRate);

                if ($completedLocation) {
                    $lifecycle->cleanup();

                    return $completedLocation;
                }
            }

            $capture->open($lock !== null);
            $source = $this->sourceResolver->resolve($song);
            $lifecycle->setSource($source);
        } catch (Throwable $e) {
            $lifecycle->cleanup();

            throw $e;
        }

        return fn () => $this->streamPrepared(
            $song,
            $bitRate,
            $startTime,
            $strategy,
            $lock,
            $capture,
            $source,
            $lifecycle,
        );
    }

    private function streamPrepared(
        Song $song,
        int $bitRate,
        float $startTime,
        TranscodingStrategy $strategy,
        ?Lock $lock,
        ProgressiveTranscodeCapture $capture,
        ProgressiveTranscodeSource $source,
        ProgressiveTranscodeLifecycle $lifecycle,
    ): void {
        $publicationDeferred = false;

        try {
            $lifecycle->keepRunningAfterDisconnect();

            $this->transcoder->streamProgressively($source->path, $bitRate, $startTime, function (string $chunk) use (
                $capture,
                $lock,
            ): void {
                $capture->write($chunk);
                $this->emit($chunk);
                throw_if(!$lock && $this->clientConnection->isDisconnected(), ClientDisconnectedException::class);
            });

            if ($lock) {
                $capture->finishWriting();
                $lifecycle->publishAtTermination(static fn () => $capture->publish($strategy, $song, $bitRate));
                $publicationDeferred = true;
            }
        } catch (ClientDisconnectedException) {
            return;
        } finally {
            if (!$publicationDeferred) {
                $lifecycle->cleanup();
            }
        }
    }

    private function emit(string $chunk): void
    {
        echo $chunk;
        $this->clientConnection->flushOutput();
    }
}
