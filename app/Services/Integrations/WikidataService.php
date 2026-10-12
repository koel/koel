<?php

namespace App\Services\Integrations;

use App\Models\Artist;
use App\Pipelines\Encyclopedia\GetArtistImageUsingWikidataId;
use App\Pipelines\Encyclopedia\GetArtistWikidataIdUsingMbid;
use Illuminate\Support\Facades\Pipeline;

class WikidataService
{
    public static function enabled(): bool
    {
        return MusicBrainzService::enabled();
    }

    public function tryGetArtistImage(Artist $artist): ?string
    {
        if (!self::enabled() || !$artist->mbid) {
            return null;
        }

        return Pipeline::send($artist->mbid)
            ->through([GetArtistWikidataIdUsingMbid::class, GetArtistImageUsingWikidataId::class])
            ->thenReturn();
    }
}
