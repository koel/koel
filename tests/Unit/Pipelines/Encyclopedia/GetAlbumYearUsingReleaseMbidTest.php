<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetReleaseGroupForReleaseRequest;
use App\Pipelines\Encyclopedia\GetAlbumYearUsingReleaseMbid;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

class GetAlbumYearUsingReleaseMbidTest extends TestCase
{
    use TestsPipelines;

    /** @return array<string, array{0: ?string, 1: ?int}> */
    public static function provideFirstReleaseDates(): array
    {
        return [
            'a full date' => ['1991-09-24', 1991],
            'a year and month' => ['1991-09', 1991],
            'a bare year' => ['1991', 1991],
            'no date' => ['', null],
            'a missing date' => [null, null],
        ];
    }

    #[Test]
    #[DataProvider('provideFirstReleaseDates')]
    public function readTheYearOfTheFirstRelease(?string $firstReleaseDate, ?int $year): void
    {
        Saloon::fake([
            GetReleaseGroupForReleaseRequest::class => MockResponse::make(body: [
                'date' => '2011-01-01',
                'release-group' => ['id' => 'sample-release-group-mbid', 'first-release-date' => $firstReleaseDate],
            ]),
        ]);

        $mock = self::createNextClosureMock($year);

        (new GetAlbumYearUsingReleaseMbid(new MusicBrainzConnector()))('sample-release-mbid', $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertSent(
            static fn (GetReleaseGroupForReleaseRequest $request): bool => (
                $request->resolveEndpoint() === '/release/sample-release-mbid'
            ),
        );
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);

        Cache::put(cache_key('album year from release mbid', 'sample-release-mbid'), 1991);

        $mock = self::createNextClosureMock(1991);

        (new GetAlbumYearUsingReleaseMbid(new MusicBrainzConnector()))('sample-release-mbid', $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }

    #[Test]
    public function justPassOnIfMbidIsNull(): void
    {
        Saloon::fake([]);
        $mock = self::createNextClosureMock(null);

        (new GetAlbumYearUsingReleaseMbid(new MusicBrainzConnector()))(null, $mock->next(...)); // @phpstan-ignore-line

        Saloon::assertNothingSent();
    }
}
