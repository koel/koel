<?php

namespace Tests\Feature\Subsonic;

use App\Events\UserUnsubscribedFromPodcast;
use App\Models\Podcast;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class DeletePodcastChannelTest extends TestCase
{
    #[Test]
    public function unsubscribesFromSubscribedPodcast(): void
    {
        Event::fake([UserUnsubscribedFromPodcast::class]);

        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($user);

        self::assertTrue($user->subscribedToPodcast($podcast));

        $this->getJson(self::urlFor($user, $podcast->id))->assertOk()->assertJsonPath('subsonic-response.status', 'ok');

        self::assertFalse($user->fresh()->subscribedToPodcast($podcast));
        Event::assertDispatched(UserUnsubscribedFromPodcast::class);
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson(self::urlFor($user, 'nonexistent-id'))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function podcastSubscribedByAnotherUserReturnsCode70(): void
    {
        $requestingUser = create_user();
        $otherUser = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($otherUser);

        $this
            ->getJson(self::urlFor($requestingUser, $podcast->id))
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);

        self::assertTrue($otherUser->fresh()->subscribedToPodcast($podcast));
    }

    private static function urlFor(User $user, string $id): string
    {
        return '/rest/deletePodcastChannel.view?'
        . Arr::query([
            'apiKey' => $user->subsonic_api_key,
            'f' => 'json',
            'id' => $id,
        ]);
    }
}
