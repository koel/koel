<?php

namespace Tests\Unit\Services;

use App\Models\Song;
use App\Services\RatingService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class RatingServiceTest extends TestCase
{
    private RatingService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(RatingService::class);
    }

    #[Test]
    public function createsRatingForFreshEntity(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this->service->setRating($song, $user, 3);

        self::assertSame(3, $song->getRatingFor($user));
    }

    #[Test]
    public function overwritesExistingRating(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this->service->setRating($song, $user, 2);
        $this->service->setRating($song, $user, 5);

        self::assertSame(5, $song->getRatingFor($user));
        self::assertSame(1, $song->ratings()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function zeroRatingDeletesRow(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this->service->setRating($song, $user, 4);
        $this->service->setRating($song, $user, 0);

        self::assertSame(0, $song->ratings()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function ratingsAreScopedPerUser(): void
    {
        $alice = create_user();
        $bob = create_user();
        $song = Song::factory()->createOne(['owner_id' => $alice->id]);

        $this->service->setRating($song, $alice, 5);
        $this->service->setRating($song, $bob, 2);

        self::assertSame(5, $song->getRatingFor($alice));
        self::assertSame(2, $song->getRatingFor($bob));
    }
}
