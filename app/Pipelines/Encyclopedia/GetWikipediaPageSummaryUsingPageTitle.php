<?php

namespace App\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikipedia\Requests\GetPageSummaryRequest;
use App\Http\Integrations\Wikipedia\WikipediaConnector;
use Closure;
use Illuminate\Support\Arr;

class GetWikipediaPageSummaryUsingPageTitle
{
    use TriesRemember;

    public function __construct(
        private readonly WikipediaConnector $connector,
    ) {}

    public function __invoke(?string $pageTitle, Closure $next): mixed
    {
        if (!$pageTitle) {
            return $next(null);
        }

        $summary = self::tryRemember(
            key: cache_key('wikipedia page summary from page title', $pageTitle),
            ttl: now()->addMonth(),
            nothingFoundTtl: now()->addWeek(),
            callback: fn (): ?array => self::trimSummaryToUsedFields(
                $this->connector->send(new GetPageSummaryRequest($pageTitle))->json(),
            ),
        );

        return $next($summary);
    }

    /**
     * @param array<string, mixed>|null $summary
     *
     * @return array<string, mixed>|null
     */
    private static function trimSummaryToUsedFields(?array $summary): ?array
    {
        if (!$summary) {
            return null;
        }

        return Arr::undot(Arr::only(Arr::dot($summary), [
            'extract',
            'extract_html',
            'content_urls.desktop.page',
            'thumbnail.source',
        ]));
    }
}
