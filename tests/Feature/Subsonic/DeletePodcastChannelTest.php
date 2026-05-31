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

        self::assertSubsonicOk($this->getSubsonic('deletePodcastChannel.view', $user, ['id' => $podcast->id]));

        self::assertFalse($user->fresh()->subscribedToPodcast($podcast));
        Event::assertDispatched(UserUnsubscribedFromPodcast::class);
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        self::assertErrorCode($this->getSubsonic('deletePodcastChannel.view', $user, [
            'id' => 'nonexistent-id',
        ]), 70);
    }

    #[Test]
    public function podcastSubscribedByAnotherUserReturnsCode70(): void
    {
        $requestingUser = create_user();
        $otherUser = create_user();
        $podcast = Podcast::factory()->createOne();
        $podcast->subscribers()->attach($otherUser);

        self::assertErrorCode($this->getSubsonic('deletePodcastChannel.view', $requestingUser, [
            'id' => $podcast->id,
        ]), 70);

        self::assertTrue($otherUser->fresh()->subscribedToPodcast($podcast));
    }
}
