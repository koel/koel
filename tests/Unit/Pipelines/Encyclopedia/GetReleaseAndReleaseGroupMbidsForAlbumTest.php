<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\SearchForReleaseRequest;
use App\Pipelines\Encyclopedia\GetReleaseAndReleaseGroupMbidsForAlbum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetReleaseAndReleaseGroupMbidsForAlbumTest extends TestCase
{
    use TestsPipelines;

    #[Test]
    public function getMbids(): void
    {
        $json = File::json(test_path('fixtures/musicbrainz/release-search.json'));

        Saloon::fake([
            SearchForReleaseRequest::class => MockResponse::make(body: $json),
        ]);

        $mock = self::createNextClosureMock(['sample-release-mbid', 'sample-release-group-mbid']);

        (new GetReleaseAndReleaseGroupMbidsForAlbum(new MusicBrainzConnector()))(
            [
                'album' => 'Slave to the Grind',
                'artist' => 'Skid Row',
            ],
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (SearchForReleaseRequest $request): bool {
            self::assertSame([
                'query' => 'release:"Slave to the Grind" AND artist:"Skid Row"',
                'limit' => 1,
            ], $request->query()->all());

            return true;
        });

        self::assertSame(
            ['sample-release-mbid', 'sample-release-group-mbid'],
            Cache::get(cache_key('release and release group mbids', 'Slave to the Grind', 'Skid Row')),
        );

        // The artist mbid should have been cached opportunistically, too.
        self::assertSame('sample-artist-mbid', Cache::get(cache_key('artist mbid', 'Skid Row')));
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(
            cache_key('release and release group mbids', 'Slave to the Grind', 'Skid Row'),
            ['sample-release-mbid', 'sample-release-group-mbid']
        );

        $mock = self::createNextClosureMock(['sample-release-mbid', 'sample-release-group-mbid']);

        (new GetReleaseAndReleaseGroupMbidsForAlbum(new MusicBrainzConnector()))(
            [
                'album' => 'Slave to the Grind',
                'artist' => 'Skid Row',
            ],
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfParamsAreNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetReleaseAndReleaseGroupMbidsForAlbum(new MusicBrainzConnector()))(
            null,
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }
}
