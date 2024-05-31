<?php

namespace Integration\Services;

use App\Exceptions\UserAlreadySubscribedToPodcast;
use App\Models\Podcast;
use App\Models\PodcastUserPivot;
use App\Models\Song;
use App\Services\PodcastService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Http;
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
            new Response(200, [], file_get_contents(test_path('blobs/podcast.xml'))),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $this->instance(ClientInterface::class, new Client(['handler' => $handlerStack]));

        $this->service = app(PodcastService::class);
    }

    public function testAddPodcast(): void
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

    public function testSubscribeUserToPodcast(): void
    {
        $podcast = Podcast::factory()->create([
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

    public function testResubscribeUserToPodcastThrows(): void
    {
        self::expectException(UserAlreadySubscribedToPodcast::class);

        $podcast = Podcast::factory()->create([
            'url' => 'https://example.com/feed.xml',
        ]);

        $user = create_user();
        $user->subscribeToPodcast($podcast);

        $this->service->addPodcast('https://example.com/feed.xml', $user);
    }

    public function testAddingRefreshesObsoletePodcast(): void
    {
        self::expectException(UserAlreadySubscribedToPodcast::class);

        Http::fake([
            'https://example.com/feed.xml' => Http::response(headers: ['Last-Modified' => now()->toRfc1123String()]),
        ]);

        $podcast = Podcast::factory()->create([
            'url' => 'https://example.com/feed.xml',
            'title' => 'Shall be changed very sad',
            'last_synced_at' => now()->subDays(3),
        ]);

        self::assertCount(0, $podcast->episodes);

        $user = create_user();
        $user->subscribeToPodcast($podcast);

        $this->service->addPodcast('https://example.com/feed.xml', $user);

        self::assertCount(8, $podcast->episodes);
        self::assertSame('Podcast Feed Parser', $podcast->title);
    }

    public function testUnsubscribeUserFromPodcast(): void
    {
        $podcast = Podcast::factory()->create();
        $user = create_user();
        $user->subscribeToPodcast($podcast);

        $this->service->unsubscribeUserFromPodcast($user, $podcast);

        self::assertFalse($user->subscribedToPodcast($podcast));
    }

    public function testPodcastNotObsoleteIfSyncedRecently(): void
    {
        $podcast = Podcast::factory()->create([
            'last_synced_at' => now()->subHours(6),
        ]);

        self::assertFalse($this->service->isPodcastObsolete($podcast));
    }

    public function testPodcastObsoleteIfModifiedSinceLastSync(): void
    {
        Http::fake([
            'https://example.com/feed.xml' => Http::response(headers: ['Last-Modified' => now()->toRfc1123String()]),
        ]);

        $podcast = Podcast::factory()->create([
            'url' => 'https://example.com/feed.xml',
            'last_synced_at' => now()->subDays(1),
        ]);

        self::assertTrue($this->service->isPodcastObsolete($podcast));
    }

    public function testUpdateEpisodeProgress(): void
    {
        $episode = Song::factory()->asEpisode()->create();
        $user = create_user();
        $user->subscribeToPodcast($episode->podcast);

        $this->service->updateEpisodeProgress($user, $episode->refresh(), 123);

        /** @var PodcastUserPivot $subscription */
        $subscription = $episode->podcast->subscribers->sole('id', $user->id)->pivot;

        self::assertSame($episode->id, $subscription->state->currentEpisode);
        self::assertSame(123, $subscription->state->progresses[$episode->id]);
    }

    public function testGetStreamableUrl(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Access-Control-Allow-Origin' => '*']),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        self::assertSame(
            'https://example.com/episode.mp3',
            $this->service->getStreamableUrl('https://example.com/episode.mp3', $client)
        );
    }

    public function testStreamableUrlNotAvailable(): void
    {
        $mock = new MockHandler([new Response(200, [])]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        self::assertNull($this->service->getStreamableUrl('https://example.com/episode.mp3', $client));
    }

    public function testGetStreamableUrlFollowsRedirects(): void
    {
        $mock = new MockHandler([
            new Response(302, ['Location' => 'https://redir.example.com/track']),
            new Response(302, ['Location' => 'https://assets.example.com/episode.mp3']),
            new Response(200, ['Access-Control-Allow-Origin' => '*']),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        self::assertSame(
            'https://assets.example.com/episode.mp3',
            $this->service->getStreamableUrl('https://example.com/episode.mp3', $client)
        );
    }
}
