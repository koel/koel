<?php

namespace App\Jobs;

use App\Exceptions\MusicBrainzBusyException;
use App\Services\Integrations\EncyclopediaService;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Illuminate\Contracts\Queue\ShouldBeUnique;

abstract class FetchEncyclopediaInformationJob extends QueuedJob implements ShouldBeUnique
{
    private const float SLOT_WAIT_SECONDS = 30.0;
    private const int RETRY_DELAY_SECONDS = 60;

    public int $tries = 10;

    abstract public function uniqueId(): string;

    /**
     * @throws MusicBrainzBusyException
     */
    abstract protected function fetchInformationOrThrowIfMusicBrainzIsBusy(EncyclopediaService $encyclopediaService): void;

    public function handle(EncyclopediaService $encyclopediaService, MusicBrainzRateLimiter $rateLimiter): void
    {
        try {
            $rateLimiter->waitForRequestSlotsUpTo(self::SLOT_WAIT_SECONDS, fn () => $this->fetchInformationOrThrowIfMusicBrainzIsBusy(
                $encyclopediaService,
            ));
        } catch (MusicBrainzBusyException) {
            $this->release(self::RETRY_DELAY_SECONDS);
        }
    }
}
