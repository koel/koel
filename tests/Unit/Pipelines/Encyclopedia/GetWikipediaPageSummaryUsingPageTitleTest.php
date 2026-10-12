<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikipedia\Requests\GetPageSummaryRequest;
use App\Http\Integrations\Wikipedia\WikipediaConnector;
use App\Pipelines\Encyclopedia\GetWikipediaPageSummaryUsingPageTitle;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetWikipediaPageSummaryUsingPageTitleTest extends TestCase
{
    use TestsPipelines;

    #[Test]
    public function getPageSummary(): void
    {
        $json = File::json(test_path('fixtures/wikipedia/artist-page-summary.json'));

        Saloon::fake([
            GetPageSummaryRequest::class => MockResponse::make(body: $json),
        ]);

        $summary = [
            'extract' => Arr::get($json, 'extract'),
            'extract_html' => Arr::get($json, 'extract_html'),
            'content_urls' => ['desktop' => ['page' => Arr::get($json, 'content_urls.desktop.page')]],
            'thumbnail' => ['source' => Arr::get($json, 'thumbnail.source')],
        ];

        $mock = self::createNextClosureMock($summary);

        (new GetWikipediaPageSummaryUsingPageTitle(new WikipediaConnector()))(
            'Skid Row (American band)',
            $mock->next(...), // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (GetPageSummaryRequest $request): bool {
            return $request->resolveEndpoint() === 'page/summary/Skid Row (American band)';
        });

        self::assertEquals(
            $summary,
            Cache::store('encyclopedia')->get(cache_key(
                'wikipedia page summary from page title',
                'Skid Row (American band)',
            )),
        );
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::store('encyclopedia')->put(
            cache_key('wikipedia page summary from page title', 'Skid Row (American band)'),
            [
                'Spider Man' => 'How’d that get in there?',
            ],
        );

        $mock = self::createNextClosureMock(['Spider Man' => 'How’d that get in there?']);

        (new GetWikipediaPageSummaryUsingPageTitle(new WikipediaConnector()))(
            'Skid Row (American band)',
            $mock->next(...), // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfPageTitleIsNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetWikipediaPageSummaryUsingPageTitle(new WikipediaConnector()))(null, $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }
}
