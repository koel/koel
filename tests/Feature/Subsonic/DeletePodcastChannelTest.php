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
        // Fake the unsubscribe event so the queued DeletePodcastIfNoSubscribers
        // listener doesn't round-trip through the sync queue — its model
        // deserialization is brittle on SQLite-in-memory's write/read PDO split.
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
