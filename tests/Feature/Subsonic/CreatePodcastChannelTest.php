<?php

namespace Tests\Feature\Subsonic;

use App\Models\Podcast;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

use function Tests\create_user;

class CreatePodcastChannelTest extends SubsonicTestCase
{
    #[Test]
    public function subscribesUserToFeed(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();

        $service = $this->mock(PodcastService::class);
        $service
            ->expects('addPodcast')
            ->once()
            ->with('https://example.com/feed.rss', Mockery::on(static fn (User $u) => $u->is($user)))
            ->andReturn($podcast);

        $this->getSubsonic('createPodcastChannel.view', $user, [
            'url' => 'https://example.com/feed.rss',
        ])->assertSubsonicOk();
    }

    #[Test]
    public function malformedUrlReturnsCode10(): void
    {
        $user = create_user();

        $this->getSubsonic('createPodcastChannel.view', $user, [
            'url' => 'not-a-real-url',
        ])->assertSubsonicErrorCode(10);
    }

    #[Test]
    public function missingUrlReturnsCode10(): void
    {
        $user = create_user();

        $this->getSubsonic('createPodcastChannel.view', $user)->assertSubsonicErrorCode(10);
    }
}
