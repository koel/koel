<?php

namespace Tests\Feature\KoelPlus\Ai;

use App\Ai\AiAssistantResult;
use App\Ai\AiRequestContext;
use App\Ai\Tools\SubscribeToPodcast;
use App\Exceptions\ApplePodcastsFeedNotFoundException;
use App\Exceptions\FailedToParsePodcastFeedException;
use App\Exceptions\UserAlreadySubscribedToPodcastException;
use App\Models\Podcast;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use Laravel\Ai\Tools\Request;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\PlusTestCase;

use function Tests\create_guest;
use function Tests\create_user;

class SubscribeToPodcastToolTest extends PlusTestCase
{
    private AiAssistantResult $result;
    private User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create_user();
        $this->result = new AiAssistantResult();

        app()->instance(AiAssistantResult::class, $this->result);
        app()->instance(AiRequestContext::class, new AiRequestContext($this->user));
    }

    #[Test]
    public function subscribeToAFeed(): void
    {
        $podcast = Podcast::factory()->createOne(['title' => 'Marktplatz']);

        $this
            ->mock(PodcastService::class)
            ->expects('addPodcast')
            ->with('https://example.com/feed.xml', $this->user)
            ->andReturn($podcast);

        $response = (string) app()
            ->make(SubscribeToPodcast::class)
            ->handle(new Request(['url' => 'https://example.com/feed.xml']));

        self::assertSame('Subscribed to the podcast "Marktplatz".', $response);
        self::assertSame('add_podcast', $this->result->action);
        self::assertTrue($this->result->data['podcast']->is($podcast));
    }

    #[Test]
    public function rejectAnInvalidUrl(): void
    {
        $this->mock(PodcastService::class)->expects('addPodcast')->never();

        $response = (string) app()->make(SubscribeToPodcast::class)->handle(new Request(['url' => 'not a url']));

        self::assertStringStartsWith('Cannot subscribe to the podcast:', $response);
        self::assertNull($this->result->action);
    }

    #[Test]
    public function reportAnExistingSubscription(): void
    {
        $podcast = Podcast::factory()->createOne();

        $this
            ->mock(PodcastService::class)
            ->expects('addPodcast')
            ->andThrow(UserAlreadySubscribedToPodcastException::create($this->user, $podcast));

        $response = (string) app()
            ->make(SubscribeToPodcast::class)
            ->handle(new Request(['url' => 'https://example.com/feed.xml']));

        self::assertSame('The user is already subscribed to this podcast.', $response);
        self::assertNull($this->result->action);
    }

    #[Test]
    public function reportAnApplePodcastsLinkWithoutAFeed(): void
    {
        $this
            ->mock(PodcastService::class)
            ->expects('addPodcast')
            ->andThrow(ApplePodcastsFeedNotFoundException::create('https://podcasts.apple.com/podcast/id1'));

        $response = (string) app()
            ->make(SubscribeToPodcast::class)
            ->handle(new Request(['url' => 'https://podcasts.apple.com/podcast/id1']));

        self::assertSame('Could not find this podcast\'s feed on Apple Podcasts.', $response);
        self::assertNull($this->result->action);
    }

    #[Test]
    public function reportAFeedThatCannotBeRead(): void
    {
        $this
            ->mock(PodcastService::class)
            ->expects('addPodcast')
            ->andThrow(FailedToParsePodcastFeedException::create(
                'https://example.com/feed.xml',
                new RuntimeException(),
            ));

        $response = (string) app()
            ->make(SubscribeToPodcast::class)
            ->handle(new Request(['url' => 'https://example.com/feed.xml']));

        self::assertSame('Could not read a podcast feed at this URL.', $response);
        self::assertNull($this->result->action);
    }

    #[Test]
    public function refuseGuests(): void
    {
        app()->instance(AiRequestContext::class, new AiRequestContext(create_guest()));
        $this->mock(PodcastService::class)->expects('addPodcast')->never();

        app()->make(SubscribeToPodcast::class)->handle(new Request(['url' => 'https://example.com/feed.xml']));

        self::assertNull($this->result->action);
    }
}
