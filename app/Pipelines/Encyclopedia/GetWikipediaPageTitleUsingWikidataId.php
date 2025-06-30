<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikidata\Requests\GetEntityDataRequest;
use App\Http\Integrations\Wikidata\WikidataConnector;
use Closure;
use Illuminate\Support\Facades\Cache;

class GetWikipediaPageTitleUsingWikidataId
{
    public function __construct(private readonly WikidataConnector $connector)
    {
    }

    public function __invoke(?string $wikidataId, Closure $next): mixed
    {
        if (!$wikidataId) {
            return $next(null);
        }

        $pageName = Cache::rememberForever(
            cache_key('wikipedia page title from wikidata id', $wikidataId),
            function () use ($wikidataId): ?string {
                return $this->connector
                    ->send(new GetEntityDataRequest($wikidataId))
                    ->json("entities.$wikidataId.sitelinks.enwiki.title");
            }
        );

        return $next($pageName);
    }
}
