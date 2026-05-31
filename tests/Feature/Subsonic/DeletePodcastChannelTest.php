<?php

namespace Tests\Feature\Subsonic;

use App\Events\UserUnsubscribedFromPodcast;
use App\Models\Podcast;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;

use function Tests\create_user;

class DeletePodcastChannelTest extends SubsonicTestCase
{
    #[Test]
    public function unsubscribesFromSubscribedPodcast(): void
    {
        Event::fake([UserUnsubscribedFromPodcast::class]);

        $user = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($user);

        self::assertTrue($user->subscribedToPodcast($podcast));

        $this->getSubsonic('deletePodcastChannel.view', $user, ['id' => $podcast->id])->assertSubsonicOk();

        self::assertFalse($user->fresh()->subscribedToPodcast($podcast));
        Event::assertDispatched(UserUnsubscribedFromPodcast::class);
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this->getSubsonic('deletePodcastChannel.view', $user, ['id' => 'nonexistent-id'])->assertSubsonicErrorCode(70);
    }

    #[Test]
    public function podcastSubscribedByAnotherUserReturnsCode70(): void
    {
        $requestingUser = create_user();
        $otherUser = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($otherUser);

        $this->getSubsonic('deletePodcastChannel.view', $requestingUser, [
            'id' => $podcast->id,
        ])->assertSubsonicErrorCode(70);

        self::assertTrue($otherUser->fresh()->subscribedToPodcast($podcast));
    }
}
