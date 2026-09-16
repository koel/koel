<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\Wikidata\Requests\GetEntityDataRequest;
use App\Http\Integrations\Wikidata\WikidataConnector;
use App\Pipelines\Encyclopedia\GetArtistImageUsingWikidataId;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetArtistImageUsingWikidataIdTest extends TestCase
{
    use TestsPipelines;

    private const string EXPECTED_URL = 'https://commons.wikimedia.org/wiki/Special:FilePath/Skid%20Row%20live%201991.jpg?width=640';

    #[Test]
    public function picksThePreferredImageAndSkipsDeprecatedAndValuelessOnes(): void
    {
        Saloon::fake([
            GetEntityDataRequest::class => MockResponse::make(body: File::json(test_path(
                'fixtures/wikidata/entity-with-image.json',
            ))),
        ]);

        $mock = self::createNextClosureMock(self::EXPECTED_URL);

        (new GetArtistImageUsingWikidataId(new WikidataConnector()))('Q461269', $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertSent(
            static fn (GetEntityDataRequest $request): bool => (
                $request->resolveEndpoint() === 'Special:EntityData/Q461269'
            ),
        );

        self::assertSame(self::EXPECTED_URL, Cache::get(cache_key('artist image from wikidata id', 'Q461269')));
    }

    #[Test]
    public function getNothingWhenTheEntityHasNoImage(): void
    {
        Saloon::fake([
            GetEntityDataRequest::class => MockResponse::make(body: File::json(test_path(
                'fixtures/wikidata/entity.json',
            ))),
        ]);

        $mock = self::createNextClosureMock(null);

        (new GetArtistImageUsingWikidataId(new WikidataConnector()))('Q461269', $mock->next(...)); // @phpstan-ignore-line
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(cache_key('artist image from wikidata id', 'Q461269'), self::EXPECTED_URL);

        $mock = self::createNextClosureMock(self::EXPECTED_URL);

        (new GetArtistImageUsingWikidataId(new WikidataConnector()))('Q461269', $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfIdIsNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetArtistImageUsingWikidataId(new WikidataConnector()))(null, $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }
}
