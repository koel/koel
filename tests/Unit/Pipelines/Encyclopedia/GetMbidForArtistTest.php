<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\SearchForArtistRequest;
use App\Pipelines\Encyclopedia\GetMbidForArtist;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetMbidForArtistTest extends TestCase
{
    use TestsPipelines;

    #[Test]
    public function getMbid(): void
    {
        $json = File::json(test_path('fixtures/musicbrainz/artist-search.json'));

        Saloon::fake([
            SearchForArtistRequest::class => MockResponse::make(body: $json),
        ]);

        $mock = self::createNextClosureMock('6da0515e-a27d-449d-84cc-00713c38a140');

        (new GetMbidForArtist(new MusicBrainzConnector()))('Skid Row', $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertSent(static function (SearchForArtistRequest $request): bool {
            self::assertSame(
                [
                    'query' => 'artist:"Skid Row"',
                    'limit' => 1,
                ],
                $request->query()->all(),
            );

            return true;
        });

        self::assertSame('6da0515e-a27d-449d-84cc-00713c38a140', Cache::get(cache_key('artist mbid', 'Skid Row')));
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);
        Cache::put(cache_key('artist mbid', 'Skid Row'), 'sample-mbid');
        $mock = self::createNextClosureMock('sample-mbid');

        (new GetMbidForArtist(new MusicBrainzConnector()))('Skid Row', $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }

    #[Test]
    public function searchOnlyOnceWhenTheArtistIsUnknownToMusicBrainz(): void
    {
        Saloon::fake([
            SearchForArtistRequest::class => MockResponse::make(body: ['artists' => []]),
        ]);

        $pipe = new GetMbidForArtist(new MusicBrainzConnector());

        $pipe('Nobody At All', self::createNextClosureMock(null)->next(...)); // @phpstan-ignore-line
        $pipe('Nobody At All', self::createNextClosureMock(null)->next(...)); // @phpstan-ignore-line

        Saloon::assertSentCount(1);
    }

    #[Test]
    public function forgetAnUnknownArtistAfterAWhile(): void
    {
        Saloon::fake([
            SearchForArtistRequest::class => MockResponse::make(body: ['artists' => []]),
        ]);

        $pipe = new GetMbidForArtist(new MusicBrainzConnector());
        $pipe('Nobody At All', self::createNextClosureMock(null)->next(...)); // @phpstan-ignore-line

        $this->travel(8)->days();

        $pipe('Nobody At All', self::createNextClosureMock(null)->next(...)); // @phpstan-ignore-line

        Saloon::assertSentCount(2);
    }

    #[Test]
    public function justPassOnIfMbidIsNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetMbidForArtist(new MusicBrainzConnector()))(null, $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }

    /** @return array<string, array{0: int}> */
    public static function provideUnavailableStatuses(): array
    {
        return [
            'busy' => [503],
            'rate limited' => [429],
            'broken' => [500],
        ];
    }

    #[Test]
    #[DataProvider('provideUnavailableStatuses')]
    public function askAgainWhenMusicBrainzIsUnavailable(int $status): void
    {
        Saloon::fake([
            SearchForArtistRequest::class => MockResponse::make(body: [
                'error' => 'The MusicBrainz web server is currently busy. Please try again later.',
            ], status: $status),
        ]);

        $mock = self::createNextClosureMock(null);

        (new GetMbidForArtist(new MusicBrainzConnector()))('Motörhead', $mock->next(...)); // @phpstan-ignore-line

        self::assertFalse(Cache::has(cache_key('artist mbid', 'Motörhead')));
    }

    #[Test]
    public function rememberAnArtistMusicBrainzDoesNotKnow(): void
    {
        Saloon::fake([
            SearchForArtistRequest::class => MockResponse::make(body: ['error' => 'Not Found'], status: 404),
        ]);

        $mock = self::createNextClosureMock(null);

        (new GetMbidForArtist(new MusicBrainzConnector()))('Motörhead', $mock->next(...)); // @phpstan-ignore-line

        self::assertTrue(Cache::has(cache_key('artist mbid', 'Motörhead')));
    }
}
