<?php

namespace App\Jobs;

use App\Models\Artist;
use App\Services\Integrations\EncyclopediaService;

class FetchArtistInformationJob extends FetchEncyclopediaInformationJob
{
    public function __construct(
        private readonly Artist $artist,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->artist->id;
    }

    protected function fetchInformationOrThrowIfMusicBrainzIsBusy(EncyclopediaService $encyclopediaService): void
    {
        $encyclopediaService->getArtistInformationOrThrowIfMusicBrainzIsBusy($this->artist);
    }
}
