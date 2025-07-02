<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetArtistUrlRelationshipsRequest;
use App\Pipelines\Encyclopedia\GetArtistWikidataIdUsingMbid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetArtistWikidataIdUsingMbidTest extends TestCase
{
    use TestsPipelines;

    #[Test]
    public function getWikidataId(): void
    {
        $json = File::json(test_path('fixtures/musicbrainz/artist-rel-urls.json'));

        Saloon::fake([
            GetArtistUrlRelationshipsRequest::class => MockResponse::make(body: $json),
        ]);

        $mock = self::createNextClosureMock('Q461269');

        (new GetArtistWikidataIdUsingMbid(new MusicBrainzConnector()))(
            'sample-mbid',
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (GetArtistUrlRelationshipsRequest $request): bool {
            self::assertSame(['inc' => 'url-rels'], $request->query()->all());

            return true;
        });

        self::assertSame(
            'Q461269',
            Cache::get(cache_key('artist wikidata id from mbid', 'sample-mbid')),
        );
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(cache_key('artist wikidata id from mbid', 'sample-mbid'), 'Q461269');

        $mock = self::createNextClosureMock('Q461269');

        (new GetArtistWikidataIdUsingMbid(new MusicBrainzConnector()))(
            'sample-mbid',
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfMbidIsNull(): void
    {
        Saloon::fake([]);

        $mock = self::createNextClosureMock(null);

        (new GetArtistWikidataIdUsingMbid(new MusicBrainzConnector()))(
            null,
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }
}
