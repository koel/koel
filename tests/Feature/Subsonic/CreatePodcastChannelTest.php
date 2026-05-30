<?php

namespace Tests\Feature\Subsonic;

use App\Models\Podcast;
use App\Models\User;
use App\Services\Podcast\PodcastService;
use Illuminate\Support\Arr;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class CreatePodcastChannelTest extends TestCase
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

        $this
            ->getJson(self::urlFor($user, 'https://example.com/feed.rss'))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }

    #[Test]
    public function malformedUrlReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson(self::urlFor($user, 'not-a-real-url'))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function missingUrlReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson(
                '/rest/createPodcastChannel.view?'
                    . Arr::query([
                        'apiKey' => $user->subsonic_api_key,
                        'f' => 'json',
                    ]),
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    private static function urlFor(User $user, string $url): string
    {
        return '/rest/createPodcastChannel.view?'
        . Arr::query([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
            'url' => $url,
        ]);
    }
}
