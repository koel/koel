<?php

namespace Tests\Integration\Services\Podcast;

use App\Events\UserUnsubscribedFromPodcast;
use App\Exceptions\ApplePodcastsFeedNotFoundException;
use App\Exceptions\FailedToParsePodcastFeedException;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
use App\Http\Integrations\iTunes\Requests\LookupPodcastRequest;
use App\Models\Podcast;
use App\Models\PodcastUserPivot;
use App\Models\Song;
use App\Services\Network\Network;
use App\Services\Podcast\PodcastService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
use Saloon\Http\Faking\MockResponse;
use Saloon\Laravel\Saloon;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class PodcastServiceTest extends TestCase
{
    private PodcastService $service;

    public function setUp(): void
    {
        parent::setUp();

        $mock = new MockHandler([
            new Response(200, [], File::get(test_path('fixtures/podcast.xml'))),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $this->instance(ClientInterface::class, new Client(['handler' => $handlerStack]));

        $this->service = app(PodcastService::class);
    }

    #[Test]
    public function addPodcastRejectsUnsafeUrl(): void
    {
        // No injected client, so the fetch goes through SafeHttp, whose SSRF guard
        // rejects the private host; addPodcast surfaces it as a parse failure.
        $this->app->forgetInstance(ClientInterface::class);

        $this->expectException(FailedToParsePodcastFeedException::class);

        app(PodcastService::class)->addPodcast('http://127.0.0.1/feed.xml', create_user());
    }

    #[Test]
    public function addPodcast(): void
    {
        $url = 'https://example.com/feed.xml';
        $user = create_user();

        $podcast = $this->service->addPodcast($url, $user);

        $this->assertDatabaseHas(Podcast::class, [
            'url' => $url,
            'title' => 'Podcast Feed Parser',
            'description' => 'Parse podcast feeds with PHP following PSP-1 Podcast RSS Standard',
            'image' => 'https://github.com/phanan.png',
            'author' => 'Phan An (phanan)',
            'language' => 'en-US',
            'explicit' => false,
            'added_by' => $user->id,
        ]);

        self::assertCount(8, $podcast->episodes);
    }

    #[Test]
    public function storeEpisodePublishDatesInTheAppTimezone(): void
    {
        $podcast = $this->service->addPodcast('https://example.com/feed.xml', create_user());

        $episode = $podcast->episodes->sole('episode_guid', 'BE486CAA-B3D5-4FB0-8298-EFEBE71C5982');

        self::assertSame('2019-04-30 18:00:00', $episode->created_at->toDateTimeString());
    }

    #[Test]
    public function addPodcastFromAnApplePodcastsLink(): void
    {
        Saloon::fake([
            LookupPodcastRequest::class => MockResponse::make(body: [
                'resultCount' => 1,
                'results' => [['feedUrl' => 'https://example.com/feed.xml']],
            ]),
        ]);

        $this->service->addPodcast(
            'https://podcasts.apple.com/us/podcast/podcast-feed-parser/id917918570',
            create_user(),
        );

        $this->assertDatabaseHas(Podcast::class, ['url' => 'https://example.com/feed.xml']);
    }

    #[Test]
    public function refuseAnApplePodcastsLinkWithoutAPublicFeed(): void
    {
        Saloon::fake([LookupPodcastRequest::class => MockResponse::make(body: ['resultCount' => 0, 'results' => []])]);

        $this->expectException(ApplePodcastsFeedNotFoundException::class);

        $this->service->addPodcast('https://podcasts.apple.com/us/podcast/exclusive/id123', create_user());
    }

    #[Test]
    public function addPodcastFollowsFeedRedirects(): void
    {
        // Some feeds (e.g. Podigee) respond with a 301 to a CDN and an empty
        // redirect body. The fetch must follow the redirect; otherwise the empty
        // body is handed to the parser and surfaces as a misleading parse error.
        $this->app->forgetInstance(ClientInterface::class);

        Http::fake([
            'https://example.com/feed.xml' => Http::response('', 301, [
                'Location' => 'https://example.org/real-feed.xml',
            ]),
            'https://example.org/real-feed.xml' => Http::response(File::get(test_path('fixtures/podcast.xml'))),
        ]);

        $podcast = app(PodcastService::class)->addPodcast('https://example.com/feed.xml', create_user());

        self::assertSame('Podcast Feed Parser', $podcast->title);
        self::assertCount(8, $podcast->episodes);
    }

    #[Test]
    public function subscribeUserToPodcast(): void
    {
        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
            'title' => 'My Cool Podcast',
        ]);

        $user = create_user();

        self::assertFalse($user->subscribedToPodcast($podcast));

        $this->service->addPodcast('https://example.com/feed.xml', $user);
        self::assertTrue($user->subscribedToPodcast($podcast));

        // the title shouldn't have changed
        self::assertSame('My Cool Podcast', $podcast->fresh()->title);
    }

    #[Test]
    public function resubscribeUserToPodcastThrows(): void
    {
        self::expectException(UserAlreadySubscribedToPodcastException::class);
        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
        ]);

        $user = create_user();
        $this->service->subscribeUserToPodcast($user, $podcast);

        $this->service->addPodcast('https://example.com/feed.xml', $user);
    }

    #[Test]
    public function addingRefreshesObsoletePodcast(): void
    {
        self::expectException(UserAlreadySubscribedToPodcastException::class);

        Http::fake([
            'https://example.com/feed.xml' => Http::response(headers: ['Last-Modified' => now()->toRfc1123String()]),
        ]);
        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
            'title' => 'Shall be changed very sad',
            'last_synced_at' => now()->subDays(3),
        ]);

        self::assertCount(0, $podcast->episodes);

        $user = create_user();
        $this->service->subscribeUserToPodcast($user, $podcast);

        $this->service->addPodcast('https://example.com/feed.xml', $user);

        self::assertCount(8, $podcast->episodes);
        self::assertSame('Podcast Feed Parser', $podcast->title);
    }

    #[Test]
    public function unsubscribeUserFromPodcast(): void
    {
        Event::fake(UserUnsubscribedFromPodcast::class);
        $podcast = Podcast::factory()->createOne();
        $user = create_user();
        $this->service->subscribeUserToPodcast($user, $podcast);

        $this->service->unsubscribeUserFromPodcast($user, $podcast);

        self::assertFalse($user->subscribedToPodcast($podcast));

        Event::assertDispatched(UserUnsubscribedFromPodcast::class, static function (UserUnsubscribedFromPodcast $event) use (
            $user,
            $podcast,
        ) {
            return $event->user->is($user) && $event->podcast->is($podcast);
        });
    }

    #[Test]
    public function podcastNotObsoleteIfSyncedRecently(): void
    {
        $podcast = Podcast::factory()->createOne([
            'last_synced_at' => now()->subHours(6),
        ]);

        self::assertFalse($this->service->isPodcastObsolete($podcast));
    }

    #[Test]
    public function podcastObsoleteIfModifiedSinceLastSync(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response(headers: ['Last-Modified' => now()->toRfc1123String()]),
        ]);
        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
            'last_synced_at' => now()->subDays(1),
        ]);

        self::assertTrue($this->service->isPodcastObsolete($podcast));
    }

    #[Test]
    public function podcastObsoleteIfNoLastModifiedHeader(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response(),
        ]);

        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
            'last_synced_at' => now()->subDays(1),
        ]);

        self::assertTrue($this->service->isPodcastObsolete($podcast));
    }

    #[Test]
    public function podcastObsoleteForcedWhenFeedUrlIsUnsafe(): void
    {
        // Even though the URL was somehow stored, isPodcastObsolete must refuse
        // to probe it — returning true so the caller falls into refreshPodcast,
        // which re-validates the URL via SafeHttp before fetching.
        $podcast = Podcast::factory()->createOne([
            'url' => 'http://127.0.0.1/feed.xml',
            'last_synced_at' => now()->subDays(1),
        ]);

        self::assertTrue($this->service->isPodcastObsolete($podcast));
    }

    #[Test]
    public function podcastObsoleteWhenFeedHeadRedirectsToPrivateHost(): void
    {
        // 302 to a private host on the HEAD probe — on_redirect throws, the
        // method's catch block returns true (treat as obsolete) and refresh
        // path will refuse to fetch from the private URL via createParser.
        Http::fake([
            'https://example.com/feed.xml' => Http::response('', 302, ['Location' => 'http://127.0.0.1/feed.xml']),
            '*' => Http::response(),
        ]);

        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
            'last_synced_at' => now()->subDays(1),
        ]);

        self::assertTrue($this->service->isPodcastObsolete($podcast));

        Http::assertNotSent(static fn (Request $request): bool => str_contains($request->url(), '127.0.0.1'));
    }

    #[Test]
    public function updateEpisodeProgress(): void
    {
        $episode = Song::factory()->asEpisode()->createOne();
        $user = create_user();
        $this->service->subscribeUserToPodcast($user, $episode->podcast);

        $this->service->updateEpisodeProgress($user, $episode->refresh(), 123);

        /** @var PodcastUserPivot $subscription */
        $subscription = $episode->podcast->subscribers->sole('id', $user->id)->pivot;

        self::assertSame($episode->id, $subscription->state->currentEpisode);
        self::assertSame(123, $subscription->state->progresses[$episode->id]);
    }

    #[Test]
    public function getStreamableUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Access-Control-Allow-Origin' => '*']),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        self::assertSame('https://example.com/episode.mp3', $this->service->getStreamableUrl(
            'https://example.com/episode.mp3',
            $client,
        ));
    }

    #[Test]
    public function streamableUrlNotAvailable(): void
    {
        $mock = new MockHandler([new Response(200, [])]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        self::assertNull($this->service->getStreamableUrl('https://example.com/episode.mp3', $client));
    }

    #[Test]
    public function getStreamableUrlFollowsRedirects(): void
    {
        $mock = new MockHandler([
            new Response(302, ['Location' => 'https://redir.example.com/track']),
            new Response(302, ['Location' => 'https://assets.example.com/episode.mp3']),
            new Response(200, ['Access-Control-Allow-Origin' => '*']),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client([
            'handler' => $handlerStack,
            'allow_redirects' => ['track_redirects' => true],
        ]);

        self::assertSame('https://assets.example.com/episode.mp3', $this->service->getStreamableUrl(
            'https://example.com/episode.mp3',
            $client,
        ));
    }

    #[Test]
    public function deletePodcast(): void
    {
        $podcast = Podcast::factory()->createOne();
        $this->service->deletePodcast($podcast);
        self::assertModelMissing($podcast);
    }

    #[Test]
    public function addPodcastSkipsEpisodesWithUnsafeEnclosureUrls(): void
    {
        $mock = new MockHandler([
            new Response(200, [], File::get(test_path('fixtures/podcast-with-unsafe-enclosures.xml'))),
        ]);

        $this->instance(ClientInterface::class, new Client(['handler' => HandlerStack::create($mock)]));
        $service = app(PodcastService::class);

        $podcast = $service->addPodcast('https://example.com/feed.xml', create_user());

        self::assertCount(1, $podcast->episodes);
        self::assertSame('https://example.com/episodes/safe.mp3', $podcast->episodes->first()->path);
    }

    /** @return array<string, array{string}> */
    public static function provideUnsafeStreamableUrls(): array
    {
        return [
            'AWS metadata IP' => ['http://169.254.169.254/latest/meta-data/'],
            'loopback IPv4' => ['http://127.0.0.1:6379/INFO'],
            'private 10.x' => ['http://10.0.0.1/admin'],
            'file scheme' => ['file:///etc/passwd'],
        ];
    }

    #[Test, DataProvider('provideUnsafeStreamableUrls')]
    public function getStreamableUrlRejectsUnsafeUrl(string $url): void
    {
        self::assertNull($this->service->getStreamableUrl($url));
    }

    #[Test]
    public function getStreamableUrlRejectsRedirectToPrivateHost(): void
    {
        // Initial URL is public, but the server 302s to 127.0.0.1. The on_redirect
        // validator must throw before the follow-up request is issued, and the
        // method returns null instead of leaking the internal target.
        $mock = new MockHandler([
            new Response(302, ['Location' => 'http://127.0.0.1/episode.mp3']),
        ]);

        $client = new Client(['handler' => HandlerStack::create($mock)]);

        self::assertNull($this->service->getStreamableUrl('https://example.com/episode.mp3', $client));
    }

    #[Test]
    public function refreshPodcastUsesLastBuildDateWhenPubDateIsStale(): void
    {
        // The fixture has pubDate=2021 (stale) and lastBuildDate=2024-05-02 (the real update date).
        // Set last_synced_at between the two: after pubDate but before lastBuildDate.
        // With the old code, pubDate (2021) < last_synced_at (2023) would cause an early return.
        // The fix picks the most recent date (lastBuildDate=2024) which is after last_synced_at.
        $mock = new MockHandler([
            new Response(200, [], File::get(test_path('fixtures/podcast-stale-pubdate.xml'))),
        ]);

        $this->instance(ClientInterface::class, new Client(['handler' => HandlerStack::create($mock)]));
        $service = app(PodcastService::class);

        $podcast = Podcast::factory()->createOne([
            'url' => 'https://example.com/feed.xml',
            'title' => 'Old Title',
            'last_synced_at' => '2023-01-01 00:00:00',
        ]);

        $service->refreshPodcast($podcast);

        self::assertSame('Podcast Feed Parser', $podcast->fresh()->title);
        self::assertCount(8, $podcast->episodes);
    }

    #[Test]
    public function addPodcastStoresEveryEpisodeOfAFeedLargerThanOneInsertBatch(): void
    {
        $items = collect(range(1, 3000))
            ->map(
                static fn (int $number): string => sprintf(
                    '<item><title>Episode %1$d</title><guid>episode-%1$d</guid>'
                    . '<enclosure url="https://example.com/%1$d.mp3" length="1" type="audio/mpeg"/></item>',
                    $number,
                ),
            )
            ->implode('');

        $feed = sprintf(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>Big</title>%s</channel></rss>',
            $items,
        );
        $mock = new MockHandler([new Response(200, [], $feed)]);

        $this->instance(ClientInterface::class, new Client(['handler' => HandlerStack::create($mock)]));

        $podcast = app(PodcastService::class)->addPodcast('https://example.com/feed.xml', create_user());

        self::assertSame(3000, $podcast->episodes()->count());
    }

    #[Test]
    public function storeAnEpisodeOnceWhenItsFeedRepeatsItsGuid(): void
    {
        $this->fakeFeeds(self::feedWithGuids('Repeats', ['same', 'same']));

        $podcast = app(PodcastService::class)->addPodcast('https://example.com/feed.xml', create_user());

        self::assertSame(1, $podcast->episodes()->count());
    }

    #[Test]
    public function letTwoPodcastsUseTheSameEpisodeGuids(): void
    {
        $this->fakeFeeds(self::feedWithGuids('First', ['1', '2']), self::feedWithGuids('Second', ['1', '2']));
        $service = app(PodcastService::class);

        $service->addPodcast('https://example.com/first.xml', create_user());
        $second = $service->addPodcast('https://example.com/second.xml', create_user());

        self::assertSame(2, $second->episodes()->count());
    }

    private function fakeFeeds(string ...$feeds): void
    {
        $responses = array_map(static fn (string $feed): Response => new Response(200, [], $feed), $feeds);

        $this->instance(ClientInterface::class, new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
        ]));
    }

    /** @param array<string> $guids */
    private static function feedWithGuids(string $title, array $guids): string
    {
        $items = collect($guids)->map(
            static fn (string $guid, int $index): string => sprintf(
                '<item><title>Episode %1$d</title><guid>%2$s</guid>'
                . '<enclosure url="https://example.com/%3$s-%1$d.mp3" length="1" type="audio/mpeg"/></item>',
                $index,
                $guid,
                $title,
            ),
        )->implode('');

        return sprintf(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>%s</title>%s</channel></rss>',
            $title,
            $items,
        );
    }

    #[Test]
    public function addPodcastChecksEachEnclosureHostOnce(): void
    {
        $network = Mockery::mock(Network::class);

        $network
            ->shouldReceive('isSafeUrl')
            ->once()
            ->with('http://example.com/podcasts/everything/AllAboutEverythingEpisode4.mp3')
            ->andReturnTrue();

        $this->instance(Network::class, $network);

        $podcast = app(PodcastService::class)->addPodcast('https://example.com/feed.xml', create_user());

        self::assertCount(8, $podcast->episodes);
    }
}
