<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetReleaseGroupUrlRelationshipsRequest;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GetAlbumWikidataIdUsingReleaseGroupMbid
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
            cache_key('album wikidata id from release group mbid', $mbid),
            function () use ($mbid): ?string {
                $wikidata = collect(Arr::where(
                    $this->connector->send(new GetReleaseGroupUrlRelationshipsRequest($mbid))->json('relations'),
                    static fn ($relation) => $relation['type'] === 'wikidata',
                ))->first();

                return $wikidata ? Str::afterLast(Arr::get($wikidata, 'url.resource'), '/') : null;
            }
        );

        return $next($wikidataId);
    }
}
