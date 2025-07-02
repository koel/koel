<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikipedia\Requests\GetPageSummaryRequest;
use App\Http\Integrations\Wikipedia\WikipediaConnector;
use App\Pipelines\Encyclopedia\GetWikipediaPageSummaryUsingPageTitle;
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

        $mock = self::createNextClosureMock($json); // we're passing the whole JSON response through the pipeline

        (new GetWikipediaPageSummaryUsingPageTitle(new WikipediaConnector()))(
            'Skid Row (American band)',
            static fn ($args) => $mock->next($args), // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (GetPageSummaryRequest $request): bool {
            return $request->resolveEndpoint() === 'page/summary/Skid Row (American band)';
        });

        self::assertSame(
            $json,
            Cache::get(cache_key('wikipedia page summary from page title', 'Skid Row (American band)')),
        );
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(
            cache_key('wikipedia page summary from page title', 'Skid Row (American band)'),
            ['Spider Man' => 'How’d that get in there?'],
        );

        $mock = self::createNextClosureMock(['Spider Man' => 'How’d that get in there?']);

        (new GetWikipediaPageSummaryUsingPageTitle(new WikipediaConnector()))(
            'Skid Row (American band)',
            static fn ($args) => $mock->next($args), // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfPageTitleIsNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetWikipediaPageSummaryUsingPageTitle(new WikipediaConnector()))(
            null,
            static fn ($args) => $mock->next($args), // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }
}
