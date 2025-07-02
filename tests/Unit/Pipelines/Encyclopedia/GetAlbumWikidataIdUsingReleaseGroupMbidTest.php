<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetReleaseGroupUrlRelationshipsRequest;
use App\Pipelines\Encyclopedia\GetAlbumWikidataIdUsingReleaseGroupMbid;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetAlbumWikidataIdUsingReleaseGroupMbidTest extends TestCase
{
    use TestsPipelines;

    #[Test]
    public function getWikidataId(): void
    {
        $json = File::json(test_path('fixtures/musicbrainz/release-group-rel-urls.json'));

        Saloon::fake([
            GetReleaseGroupUrlRelationshipsRequest::class => MockResponse::make(body: $json),
        ]);

        $mock = self::createNextClosureMock('Q1929918');

        (new GetAlbumWikidataIdUsingReleaseGroupMbid(new MusicBrainzConnector()))(
            'sample-mbid',
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (GetReleaseGroupUrlRelationshipsRequest $request): bool {
            self::assertSame(['inc' => 'url-rels'], $request->query()->all());

            return true;
        });

        self::assertSame(
            'Q1929918',
            Cache::get(cache_key('album wikidata id from release group mbid', 'sample-mbid')),
        );
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(cache_key('album wikidata id from release group mbid', 'sample-mbid'), 'Q1929918');

        $mock = self::createNextClosureMock('Q1929918');

        (new GetAlbumWikidataIdUsingReleaseGroupMbid(new MusicBrainzConnector()))(
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

        (new GetAlbumWikidataIdUsingReleaseGroupMbid(new MusicBrainzConnector()))(
            null,
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }
}
