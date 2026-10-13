<?php

namespace App\Jobs;

use App\Models\Album;
use App\Services\Integrations\EncyclopediaService;

class FetchAlbumInformationJob extends FetchEncyclopediaInformationJob
{
    public function __construct(
        private readonly Album $album,
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->album->id;
    }

    protected function fetchInformationOrThrowIfMusicBrainzIsBusy(EncyclopediaService $encyclopediaService): void
    {
        $encyclopediaService->getAlbumInformationOrThrowIfMusicBrainzIsBusy($this->album);
    }
}
