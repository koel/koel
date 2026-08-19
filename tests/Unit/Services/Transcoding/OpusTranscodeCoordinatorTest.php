<?php

namespace Tests\Unit\Services\Transcoding;

use App\Enums\TranscodeCodec;
use App\Exceptions\TranscodingFailedException;
use App\Models\Song;
use App\Services\Transcoding\OpusTranscodeCoordinator;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpusTranscodeCoordinatorTest extends TestCase
{
    #[Test]
    public function derivesLeaseAndWaitFromConfiguredTranscodeTimeout(): void
    {
        $song = Song::factory()->createOne();
        $lock = Mockery::mock(Lock::class);
        Cache::expects('lock')->with($this->lockKey($song, 256), 1_260)->andReturn($lock);
        $lock->expects('block')->with(660)->andReturnTrue();

        $acquiredLock = (new OpusTranscodeCoordinator(transcodeTimeout: 300))->acquire($song, 256);

        self::assertSame($lock, $acquiredLock);
    }

    #[Test]
    public function usesFiniteWaitForUnlimitedTranscoding(): void
    {
        $song = Song::factory()->createOne();
        $lock = Mockery::mock(Lock::class);
        Cache::expects('lock')->with($this->lockKey($song, 256), 86_400)->andReturn($lock);
        $lock->expects('block')->with(3_600)->andReturnTrue();

        $acquiredLock = (new OpusTranscodeCoordinator(transcodeTimeout: 0))->acquire($song, 256);

        self::assertSame($lock, $acquiredLock);
    }

    #[Test]
    public function reportsWaitTimeoutWithoutReleasingUnownedLock(): void
    {
        $song = Song::factory()->createOne();
        $lock = Mockery::mock(Lock::class);
        Cache::expects('lock')->andReturn($lock);
        $lock->expects('block')->andThrow(new LockTimeoutException());
        $lock->expects('release')->never();
        $lock->expects('forceRelease')->never();

        $this->expectException(TranscodingFailedException::class);
        $this->expectExceptionMessage('timed out waiting for Opus cache production');

        (new OpusTranscodeCoordinator(transcodeTimeout: 300))->runExclusively($song, 256, static fn () => null);
    }

    private function lockKey(Song $song, int $bitRate): string
    {
        return sprintf('transcode-cache:%s:%d:%s', $song->id, $bitRate, TranscodeCodec::OPUS->value);
    }
}
