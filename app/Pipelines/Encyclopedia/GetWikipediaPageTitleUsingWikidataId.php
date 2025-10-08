<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikidata\Requests\GetEntityDataRequest;
use App\Http\Integrations\Wikidata\WikidataConnector;
use Closure;

class GetWikipediaPageTitleUsingWikidataId
{
    use TriesRemember;

    public function __construct(private readonly WikidataConnector $connector)
    {
    }

    public function __invoke(?string $wikidataId, Closure $next): mixed
    {
        if (!$wikidataId) {
            return $next(null);
        }

        $pageTitle = $this->tryRememberForever(
            key: cache_key('wikipedia page title from wikidata id', $wikidataId),
            callback: fn () => $this->connector
                ->send(new GetEntityDataRequest($wikidataId))
                ->json("entities.$wikidataId.sitelinks.enwiki.title"),
        );

        return $next($pageTitle);
    }
}
