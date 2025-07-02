<?php

namespace Tests\Unit\Pipelines\Encyclopedia;

use App\Http\Integrations\MusicBrainz\MusicBrainzConnector;
use App\Http\Integrations\MusicBrainz\Requests\GetRecordingsRequest;
use App\Pipelines\Encyclopedia\GetAlbumTracksUsingMbid;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Facades\Saloon;
use Tests\Concerns\TestsPipelines;
use Tests\TestCase;

use function Tests\test_path;

class GetAlbumTracksUsingMbidTest extends TestCase
{
    use TestsPipelines;

    private array $responseBody;
    private array $tracks;

    public function setUp(): void
    {
        parent::setUp();

        $this->responseBody = File::json(test_path('fixtures/musicbrainz/recordings.json'));
        $this->tracks = [];

        foreach (Arr::get($this->responseBody, 'media') as $media) {
            array_push($this->tracks, ...Arr::get($media, 'tracks', []));
        }
    }

    protected function tearDown(): void
    {
        Cache::clear();

        parent::tearDown();
    }

    #[Test]
    public function getRecordings(): void
    {
        Saloon::fake([
            GetRecordingsRequest::class => MockResponse::make(body: $this->responseBody),
        ]);

        $mock = self::createNextClosureMock($this->tracks);

        (new GetAlbumTracksUsingMbid(new MusicBrainzConnector()))(
            'sample-mbid',
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertSent(static function (GetRecordingsRequest $request): bool {
            self::assertSame(['inc' => 'recordings'], $request->query()->all());

            return true;
        });

        self::assertEquals($this->tracks, Cache::get(cache_key('album tracks', 'sample-mbid')));
    }

    #[Test]
    public function getFromCache(): void
    {
        Saloon::fake([]);
        Cache::put(cache_key('album tracks', 'sample-mbid'), $this->tracks);

        $mock = self::createNextClosureMock($this->tracks);

        (new GetAlbumTracksUsingMbid(new MusicBrainzConnector()))(
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

        (new GetAlbumTracksUsingMbid(new MusicBrainzConnector()))(
            null,
            static fn ($args) => $mock->next($args) // @phpstan-ignore-line
        );

        Saloon::assertNothingSent();
    }
}
