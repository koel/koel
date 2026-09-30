<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;
use App\Services\Integrations\MusicBrainzRateLimiter;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class FetchArtistInformationJob extends QueuedJob implements ShouldBeUnique
{
    private const float SLOT_WAIT_SECONDS = 60.0;

    public function __construct(
        private readonly Artist $artist,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->artist->id;
    }

    public function handle(EncyclopediaService $encyclopediaService, MusicBrainzRateLimiter $rateLimiter): void
    {
        $rateLimiter->waitUpTo(self::SLOT_WAIT_SECONDS, fn () => $encyclopediaService->getArtistInformation($this->artist));
    }
}
