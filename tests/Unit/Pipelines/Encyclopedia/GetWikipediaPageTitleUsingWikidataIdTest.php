<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikidata\Requests\GetEntityDataRequest;
use App\Http\Integrations\Wikidata\WikidataConnector;
use App\Pipelines\Encyclopedia\GetWikipediaPageTitleUsingWikidataId;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetWikipediaPageTitleUsingWikidataIdTest extends TestCase
{
    use TestsPipelines;

    #[Test]
    public function getName(): void
    {
        $json = File::json(test_path('fixtures/wikidata/entity.json'));

        Saloon::fake([
            GetEntityDataRequest::class => MockResponse::make(body: $json),
        ]);

        $mock = self::createNextClosureMock('Skid Row (American band)');

        (new GetWikipediaPageTitleUsingWikidataId(new WikidataConnector()))(
            'Q461269',
            static fn ($args) => $mock->next($args), // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (GetEntityDataRequest $request): bool {
            return $request->resolveEndpoint() === 'Special:EntityData/Q461269';
        });

        self::assertSame(
            'Skid Row (American band)',
            Cache::get(cache_key('wikipedia page title from wikidata id', 'Q461269')),
        );
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(
            cache_key('wikipedia page title from wikidata id', 'Q461269'),
            'How’d that get in there?'
        );

        $mock = self::createNextClosureMock('How’d that get in there?');

        (new GetWikipediaPageTitleUsingWikidataId(new WikidataConnector()))(
            'Q461269',
            static fn ($args) => $mock->next($args), // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfIdIsNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetWikipediaPageTitleUsingWikidataId(new WikidataConnector()))(
            null,
            static fn ($args) => $mock->next($args), // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }
}
