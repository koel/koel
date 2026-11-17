<?php

namespace Tests\Integration\Services\Integrations;

use App\Http\Integrations\YouTube\Requests\SearchVideosRequest;
use App\Models\Artist;
use App\Models\Organization;
use App\Models\Song;
use App\Services\Integrations\YouTubeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Response;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\test_path;

class YouTubeServiceTest extends TestCase
{
    private YouTubeService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(YouTubeService::class);
    }

    #[Test]
    public function searchVideosRelatedToSong(): void
    {
        $song = Song::factory()->for(Artist::factory()->createOne(['name' => 'Slipknot']))->createOne([
            'title' => 'Snuff',
        ]);

        Saloon::fake([
            SearchVideosRequest::class => MockResponse::make(body: File::get(test_path(
                'fixtures/youtube/search.json',
            ))),
        ]);

        $response = $this->service->searchVideosRelatedToSong($song, Organization::default(), 'my-token');

        self::assertSame('Slipknot - Snuff [OFFICIAL VIDEO]', $response->items[0]->snippet->title);
        self::assertNotNull(Cache::get('43d0e04621642165c5a3052b884945b9'));

        Saloon::assertSent(static function (SearchVideosRequest $request): bool {
            self::assertSame(
                [
                    'part' => 'snippet',
                    'type' => 'video',
                    'maxResults' => 10,
                    'pageToken' => 'my-token',
                    'q' => 'Snuff Slipknot',
                ],
                $request->query()->all(),
            );

            return true;
        });
    }

    #[Test]
    public function retryAFailedSearchInsteadOfCachingTheFailure(): void
    {
        $song = Song::factory()->for(Artist::factory()->createOne(['name' => 'Slipknot']))->createOne([
            'title' => 'Snuff',
        ]);

        Saloon::fake([
            SearchVideosRequest::class => MockResponse::make(body: ['error' => 'quotaExceeded'], status: 403),
        ]);

        $failedResponse = $this->service->searchVideosRelatedToSong($song, Organization::default());

        self::assertNull($failedResponse);

        Saloon::fake([
            SearchVideosRequest::class => MockResponse::make(body: File::get(test_path(
                'fixtures/youtube/search.json',
            ))),
        ]);

        $response = $this->service->searchVideosRelatedToSong($song, Organization::default());

        self::assertSame('Slipknot - Snuff [OFFICIAL VIDEO]', $response->items[0]->snippet->title);
    }

    #[Test]
    public function searchWithTheStoredApiKey(): void
    {
        $this->service->updateApiKey(Organization::default(), 'stored-key');

        Saloon::fake([
            SearchVideosRequest::class => MockResponse::make(body: File::get(test_path(
                'fixtures/youtube/search.json',
            ))),
        ]);

        $this->service->searchVideosRelatedToSong(Song::factory()->createOne(), Organization::default());

        Saloon::assertSent(
            static fn (SearchVideosRequest $request, Response $response): bool => (
                $response->getPendingRequest()->query()->get('key') === 'stored-key'
            ),
        );
    }

    #[Test]
    public function fallBackToTheInstallWideApiKey(): void
    {
        $this->service->updateApiKey(Organization::default(), 'stored-key');
        $this->service->updateApiKey(Organization::default(), null);

        self::assertFalse($this->service->hasStoredApiKey(Organization::default()));
        self::assertSame('foo', $this->service->getApiKey(Organization::default()));
    }

    #[Test]
    public function skipTheSearchWithoutAnApiKey(): void
    {
        config(['koel.services.youtube.key' => null]);
        Saloon::fake([]);

        self::assertNull(app(YouTubeService::class)->searchVideosRelatedToSong(
            Song::factory()->createOne(),
            Organization::default(),
        ));

        Saloon::assertNothingSent();
    }
}
