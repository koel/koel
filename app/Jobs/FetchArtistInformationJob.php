<?php

namespace App\Jobs;

use App\Exceptions\MusicBrainzBusyException;
use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class FetchArtistInformationJob extends QueuedJob implements ShouldBeUnique
{
    private const float SLOT_WAIT_SECONDS = 30.0;
    private const int RETRY_DELAY_SECONDS = 60;

    public int $tries = 10;

    public function __construct(
        private readonly Artist $artist,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->artist->id;
    }

    public function handle(EncyclopediaService $encyclopediaService, MusicBrainzRateLimiter $rateLimiter): void
    {
        try {
            $rateLimiter->waitForRequestSlotsUpTo(self::SLOT_WAIT_SECONDS, fn () => $encyclopediaService->getArtistInformationOrThrowIfMusicBrainzIsBusy($this->artist));
        } catch (MusicBrainzBusyException) {
            $this->release(self::RETRY_DELAY_SECONDS);
        }
    }
}
