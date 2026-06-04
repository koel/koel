<?php

namespace Tests\Feature;

use App\Models\Podcast;
use App\Models\Rating;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RatePodcastTest extends TestCase
{
    private function subscribedPodcast(\App\Models\User $user): Podcast
    {
        $podcast = Podcast::factory()->createOne();
        $user->podcasts()->attach($podcast);

        return $podcast;
    }

    #[Test]
    public function ratesAPodcast(): void
    {
        $user = create_user();
        $podcast = $this->subscribedPodcast($user);

        $this
            ->putAs("api/podcasts/$podcast->id/rating", ['rating' => 4], $user)
            ->assertOk()
            ->assertJsonPath('id', $podcast->id)
            ->assertJsonPath('rating', 4);

        $this->assertDatabaseHas(Rating::class, [
            'user_id' => $user->id,
            'rateable_id' => $podcast->id,
            'rateable_type' => $podcast->getMorphClass(),
            'rating' => 4,
        ]);
    }

    #[Test]
    public function updatesAnExistingRating(): void
    {
        $user = create_user();
        $podcast = $this->subscribedPodcast($user);

        $this->putAs("api/podcasts/$podcast->id/rating", ['rating' => 2], $user)->assertOk();
        $this
            ->putAs("api/podcasts/$podcast->id/rating", ['rating' => 5], $user)
            ->assertOk()
            ->assertJsonPath('rating', 5);

        self::assertSame(1, Rating::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingZeroClearsExistingRating(): void
    {
        $user = create_user();
        $podcast = $this->subscribedPodcast($user);
        $this->putAs("api/podcasts/$podcast->id/rating", ['rating' => 3], $user)->assertOk();

        $this
            ->putAs("api/podcasts/$podcast->id/rating", ['rating' => 0], $user)
            ->assertOk()
            ->assertJsonPath('rating', 0);

        self::assertSame(0, Rating::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingPersistsScopedToUser(): void
    {
        $alice = create_user();
        $bob = create_user();
        $podcast = Podcast::factory()->createOne();
        $alice->podcasts()->attach($podcast);

        Rating::factory()->for($bob)->for($podcast, 'rateable')->createOne(['rating' => 1]);

        $this
            ->putAs("api/podcasts/$podcast->id/rating", ['rating' => 5], $alice)
            ->assertOk()
            ->assertJsonPath('rating', 5);

        self::assertSame(5, $podcast->fresh()->getRatingFor($alice));
        self::assertSame(1, $podcast->fresh()->getRatingFor($bob));
    }

    #[Test]
    public function rejectsRatingForPodcastUserIsNotSubscribedTo(): void
    {
        $user = create_user();
        $podcast = Podcast::factory()->createOne();

        $this->putAs("api/podcasts/$podcast->id/rating", ['rating' => 4], $user)->assertForbidden();
    }

    #[Test]
    public function rejectsOutOfRangeRating(): void
    {
        $user = create_user();
        $podcast = $this->subscribedPodcast($user);

        $this->putAs("api/podcasts/$podcast->id/rating", ['rating' => 6], $user)->assertUnprocessable();
        $this->putAs("api/podcasts/$podcast->id/rating", ['rating' => -1], $user)->assertUnprocessable();
    }

    #[Test]
    public function rejectsMissingRating(): void
    {
        $user = create_user();
        $podcast = $this->subscribedPodcast($user);

        $this->putAs("api/podcasts/$podcast->id/rating", [], $user)->assertUnprocessable();
    }
}
