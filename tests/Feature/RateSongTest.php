<?php

namespace Tests\Feature;

use App\Models\Rating;
use App\Models\Song;
use App\Services\RatingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RateSongTest extends TestCase
{
    #[Test]
    public function ratesASong(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        $this
            ->putAs("api/songs/$song->id/rating", ['rating' => 4], $user)
            ->assertOk()
            ->assertJsonPath('id', $song->id)
            ->assertJsonPath('rating', 4);

        $this->assertDatabaseHas(Rating::class, [
            'user_id' => $user->id,
            'rateable_id' => $song->id,
            'rateable_type' => $song->getMorphClass(),
            'rating' => 4,
        ]);
    }

    #[Test]
    public function updatesAnExistingRating(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        $this->putAs("api/songs/$song->id/rating", ['rating' => 2], $user)->assertOk();
        $this->putAs("api/songs/$song->id/rating", ['rating' => 5], $user)->assertOk()->assertJsonPath('rating', 5);

        self::assertSame(1, Rating::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingZeroClearsExistingRating(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();
        $this->putAs("api/songs/$song->id/rating", ['rating' => 3], $user)->assertOk();

        $this->putAs("api/songs/$song->id/rating", ['rating' => 0], $user)->assertOk()->assertJsonPath('rating', 0);

        self::assertSame(0, Rating::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingPersistsScopedToUser(): void
    {
        $alice = create_user();
        $bob = create_user();
        $song = Song::factory()->createOne();

        // Bob pre-rates from outside the HTTP layer so we can verify Alice's
        // write doesn't disturb Bob's rating.
        app(RatingService::class)->setRating($song, $bob, 1);

        $this->putAs("api/songs/$song->id/rating", ['rating' => 5], $alice)->assertOk()->assertJsonPath('rating', 5);

        self::assertSame(5, $song->fresh()->getRatingFor($alice));
        self::assertSame(1, $song->fresh()->getRatingFor($bob));
    }

    #[Test]
    public function rejectsOutOfRangeRating(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        $this->putAs("api/songs/$song->id/rating", ['rating' => 6], $user)->assertUnprocessable();
        $this->putAs("api/songs/$song->id/rating", ['rating' => -1], $user)->assertUnprocessable();
    }

    #[Test]
    public function rejectsMissingRating(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne();

        $this->putAs("api/songs/$song->id/rating", [], $user)->assertUnprocessable();
    }
}
