<?php

namespace Tests\Integration\Services\Podcast;

use App\Events\UserUnsubscribedFromPodcast;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
use App\Models\Podcast;
use App\Models\PodcastUserPivot;
use App\Models\Song;
use App\Services\Podcast\PodcastService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Client\ClientInterface;
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
            new Response(200, [], file_get_contents(test_path('fixtures/podcast.xml'))),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $this->instance(ClientInterface::class, new Client(['handler' => $handlerStack]));

        $this->service = app(PodcastService::class);
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
        $client = new Client(['handler' => $handlerStack]);

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
    public function refreshPodcastUsesLastBuildDateWhenPubDateIsStale(): void
    {
        // The fixture has pubDate=2021 (stale) and lastBuildDate=2024-05-02 (the real update date).
        // Set last_synced_at between the two: after pubDate but before lastBuildDate.
        // With the old code, pubDate (2021) < last_synced_at (2023) would cause an early return.
        // The fix picks the most recent date (lastBuildDate=2024) which is after last_synced_at.
        $mock = new MockHandler([
            new Response(200, [], file_get_contents(test_path('fixtures/podcast-stale-pubdate.xml'))),
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
}
