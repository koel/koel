<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikipedia\Requests\GetPageSummaryRequest;
use App\Http\Integrations\Wikipedia\WikipediaConnector;
use Closure;
use Illuminate\Support\Facades\Cache;

class GetWikipediaPageSummaryUsingPageTitle
{
    public function __construct(private readonly WikipediaConnector $connector)
    {
    }

    public function __invoke(?string $pageTitle, Closure $next): mixed
    {
        if (!$pageTitle) {
            return $next(null);
        }

        $summary = Cache::remember(
            cache_key('wikipedia page summary from page title', $pageTitle),
            now()->addMonth(),
            function () use ($pageTitle): array {
                return $this->connector
                    ->send(new GetPageSummaryRequest($pageTitle))
                    ->json();
            }
        );

        return $next($summary);
    }
}
