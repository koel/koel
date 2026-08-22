<?php

namespace Tests\Feature;

use App\Models\Podcast;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class PodcastEpisodeTest extends TestCase
{
    private static function makeSubscribedPodcast(User $user): Podcast
    {
        $podcast = Podcast::factory()->createOne();
        $user->podcasts()->attach($podcast);

        return $podcast;
    }

    #[Test]
    public function listsEpisodesForASubscribedPodcast(): void
    {
        $user = create_user();
        $podcast = self::makeSubscribedPodcast($user);

        $this->getAs("api/podcasts/$podcast->id/episodes", $user)->assertOk();
    }

    #[Test]
    public function rejectsListingEpisodesOfAPodcastUserIsNotSubscribedTo(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();

        $this->getAs("api/podcasts/$podcast->id/episodes", $user)->assertForbidden();
    }

    #[Test]
    public function refreshesAPodcastTheUserIsSubscribedTo(): void
    {
        $user = create_user();
        $podcast = self::makeSubscribedPodcast($user);

        $this->mock(PodcastService::class)->expects('refreshPodcast');

        $this->getAs("api/podcasts/$podcast->id/episodes?refresh=true", $user)->assertOk();
    }

    #[Test]
    public function doesNotRefreshAPodcastUserIsNotSubscribedTo(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();

        $this->mock(PodcastService::class)->shouldNotReceive('refreshPodcast');

        $this->getAs("api/podcasts/$podcast->id/episodes?refresh=true", $user)->assertForbidden();
    }
}
