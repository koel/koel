<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Rating;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RateAlbumTest extends TestCase
{
    #[Test]
    public function ratesAnAlbum(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne();

        $this
            ->putAs("api/albums/$album->id/rating", ['rating' => 4], $user)
            ->assertOk()
            ->assertJsonPath('id', $album->id)
            ->assertJsonPath('rating', 4);

        $this->assertDatabaseHas(Rating::class, [
            'user_id' => $user->id,
            'rateable_id' => $album->id,
            'rateable_type' => $album->getMorphClass(),
            'rating' => 4,
        ]);
    }

    #[Test]
    public function updatesAnExistingRating(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne();

        $this->putAs("api/albums/$album->id/rating", ['rating' => 2], $user)->assertOk();
        $this->putAs("api/albums/$album->id/rating", ['rating' => 5], $user)->assertOk()->assertJsonPath('rating', 5);

        self::assertSame(1, Rating::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingZeroClearsExistingRating(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne();
        $this->putAs("api/albums/$album->id/rating", ['rating' => 3], $user)->assertOk();

        $this->putAs("api/albums/$album->id/rating", ['rating' => 0], $user)->assertOk()->assertJsonPath('rating', 0);

        self::assertSame(0, Rating::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingPersistsScopedToUser(): void
    {
        $alice = create_user();
        $bob = create_user();
        $album = Album::factory()->createOne();

        Rating::factory()->for($bob)->for($album, 'rateable')->createOne(['rating' => 1]);

        $this->putAs("api/albums/$album->id/rating", ['rating' => 5], $alice)->assertOk()->assertJsonPath('rating', 5);

        self::assertSame(5, $album->fresh()->getRatingFor($alice));
        self::assertSame(1, $album->fresh()->getRatingFor($bob));
    }

    #[Test]
    public function rejectsOutOfRangeRating(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne();

        $this->putAs("api/albums/$album->id/rating", ['rating' => 6], $user)->assertUnprocessable();
        $this->putAs("api/albums/$album->id/rating", ['rating' => -1], $user)->assertUnprocessable();
    }

    #[Test]
    public function rejectsMissingRating(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne();

        $this->putAs("api/albums/$album->id/rating", [], $user)->assertUnprocessable();
    }
}
