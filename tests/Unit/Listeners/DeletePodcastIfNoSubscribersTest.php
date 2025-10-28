<?php

namespace Tests\Unit\Listeners;

use App\Events\UserUnsubscribedFromPodcast;
use App\Listeners\DeletePodcastIfNoSubscribers;
use App\Models\Podcast;
use App\Services\PodcastService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DeletePodcastIfNoSubscribersTest extends TestCase
{
    private MockInterface|PodcastService $podcastService;
    private DeletePodcastIfNoSubscribers $listener;

    public function setUp(): void
    {
        parent::setUp();

        $this->podcastService = Mockery::mock(PodcastService::class);
        $this->listener = new DeletePodcastIfNoSubscribers($this->podcastService);
    }

    #[Test]
    public function handlePodcastWithNoSubscribers(): void
    {
        /** @var Podcast $podcast */
        $podcast = Podcast::factory()->create();

        $this->podcastService->expects('deletePodcast')->once()->with($podcast);

        $this->listener->handle(new UserUnsubscribedFromPodcast(create_user(), $podcast));
    }

    #[Test]
    public function handlePodcastWithSubscribers(): void
    {
        /** @var Podcast $podcast */
        $podcast = Podcast::factory()->create();
        $podcast->subscribers()->attach(create_user());

        $this->podcastService->expects('deletePodcast')->never();

        $this->listener->handle(new UserUnsubscribedFromPodcast(create_user(), $podcast));
    }
}
