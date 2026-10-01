<?php

namespace App\Services\Integrations;

use App\Exceptions\MusicBrainzBusyException;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;

/**
 * Keeps every process of this installation — web requests, queue workers and commands alike — to one
 * MusicBrainz request per second, the rate MusicBrainz allows per IP address.
 *
 * @link https://musicbrainz.org/doc/MusicBrainz_API/Rate_Limiting
 */
#[Singleton]
class MusicBrainzRateLimiter
{
    private const string SLOT_MUTEX_KEY = 'musicbrainz request slot mutex';
    private const string NEXT_SLOT_KEY = 'musicbrainz next request slot at';
    private const float BACK_OFF_SECONDS = 30.0;
    private const float MUTEX_RETRY_SECONDS = 0.05;
    private const float INLINE_WAIT_SECONDS = 3.0;

    private ?float $slotDeadline = null;

    public function __construct(
        #[Config('queue.default')]
        private readonly string $queueConnection = 'sync',
        private readonly float $requestIntervalSeconds = 1.0,
    ) {}

    /**
     * Whether a lookup that finds no free slot can be left to a queued job instead of the request.
     */
    public function canQueueLookups(): bool
    {
        return $this->queueConnection !== 'sync';
    }

    /**
     * Lets the requests made inside the callback wait for slots, all of them together for at most
     * the given number of seconds — so a job holding several lookups stays inside a worker's timeout.
     *
     * @template TResult
     *
     * @param Closure(): TResult $callback
     *
     * @return TResult
     */
    public function waitForRequestSlotsUpTo(float $seconds, Closure $callback): mixed
    {
        $previousDeadline = $this->slotDeadline;
        $this->slotDeadline = self::currentTimeInSeconds() + $seconds;

        try {
            return $callback();
        } finally {
            $this->slotDeadline = $previousDeadline;
        }
    }

    public function takeRequestSlot(): void
    {
        $deadline = $this->slotDeadline ?? (self::currentTimeInSeconds() + $this->resolveDefaultWaitSeconds());

        while (true) {
            $waitSeconds = $this->tryTakeRequestSlot();

            if ($waitSeconds === 0.0) {
                return;
            }

            throw_if((self::currentTimeInSeconds() + $waitSeconds) > $deadline, MusicBrainzBusyException::create());

            Sleep::usleep((int) ceil($waitSeconds * 1_000_000));
        }
    }

    public function backOff(): void
    {
        Cache::lock(self::SLOT_MUTEX_KEY, 5)->block(1, static function (): void {
            $backOffUntil = self::currentTimeInSeconds() + self::BACK_OFF_SECONDS;

            Cache::forever(self::NEXT_SLOT_KEY, max($backOffUntil, (float) Cache::get(self::NEXT_SLOT_KEY, 0)));
        });
    }

    private function resolveDefaultWaitSeconds(): float
    {
        return $this->canQueueLookups() ? 0.0 : self::INLINE_WAIT_SECONDS;
    }

    /**
     * @return float 0 when the slot was taken, otherwise how long to wait before asking again
     */
    private function tryTakeRequestSlot(): float
    {
        $waitSeconds = Cache::lock(self::SLOT_MUTEX_KEY, 5)->get(function (): float {
            $now = self::currentTimeInSeconds();
            $nextSlotAt = (float) Cache::get(self::NEXT_SLOT_KEY, 0);

            if ($now < $nextSlotAt) {
                return $nextSlotAt - $now;
            }

            Cache::forever(self::NEXT_SLOT_KEY, $now + $this->requestIntervalSeconds);

            return 0.0;
        });

        return $waitSeconds === false ? self::MUTEX_RETRY_SECONDS : $waitSeconds;
    }

    private static function currentTimeInSeconds(): float
    {
        return (float) now()->format('U.u');
    }
}
