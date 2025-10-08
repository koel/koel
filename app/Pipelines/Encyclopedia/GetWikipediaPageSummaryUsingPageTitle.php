<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikipedia\Requests\GetPageSummaryRequest;
use App\Http\Integrations\Wikipedia\WikipediaConnector;
use Closure;

class GetWikipediaPageSummaryUsingPageTitle
{
    use RemembersForever;

    public function __construct(private readonly WikipediaConnector $connector)
    {
    }

    public function __invoke(?string $pageTitle, Closure $next): mixed
    {
        if (!$pageTitle) {
            return $next(null);
        }

        $summary = $this->tryRememberForever(
            key: cache_key('wikipedia page summary from page title', $pageTitle),
            callback: fn () => $this->connector
                ->send(new GetPageSummaryRequest($pageTitle))
                ->json(),
        );

        return $next($summary);
    }
}
