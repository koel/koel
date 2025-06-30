<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetArtistUrlRelationshipsRequest;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GetArtistWikidataIdUsingMbid
{
    public function __construct(private readonly MusicBrainzConnector $connector)
    {
    }

    public function __invoke(?string $mbid, Closure $next): mixed
    {
        if (!$mbid) {
            return $next(null);
        }

        $wikidataId = Cache::rememberForever(
            cache_key('artist wikidata id from mbid', $mbid),
            function () use ($mbid): ?string {
                $wikidata = collect(Arr::where(
                    $this->connector->send(new GetArtistUrlRelationshipsRequest($mbid))->json('relations'),
                    static fn ($relation) => $relation['type'] === 'wikidata',
                ))->first();

                return $wikidata
                    ? Str::afterLast(Arr::get($wikidata, 'url.resource'), '/') // 'https://www.wikidata.org/wiki/Q461269'
                    : null;
            }
        );

        return $next($wikidataId);
    }
}
