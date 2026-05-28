<?php

namespace Tests\Feature\Subsonic;

use App\Models\Rating;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class SetRatingTest extends TestCase
{
    #[Test]
    public function positiveRatingPersistsForTheUser(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this
            ->getJson("/rest/setRating.view?apiKey={$user->subsonic_api_key}" . "&f=json&id={$song->id}&rating=4")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertSame(4, $song->getRatingFor($user));
    }

    #[Test]
    public function zeroRatingRemovesExistingRating(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);
        Rating::factory()->createOne([
            'user_id' => $user->id,
            'rateable_type' => $song->getMorphClass(),
            'rateable_id' => $song->id,
            'rating' => 3,
        ]);

        $this->getJson(
            "/rest/setRating.view?apiKey={$user->subsonic_api_key}" . "&f=json&id={$song->id}&rating=0",
        )->assertOk();

        self::assertSame(0, $song->getRatingFor($user));
    }

    #[Test]
    public function nonNumericRatingIsRejected(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this
            ->getJson("/rest/setRating.view?apiKey={$user->subsonic_api_key}&f=json&id={$song->id}&rating=foo")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);

        self::assertSame(0, $song->getRatingFor($user));
    }

    #[Test]
    public function ratingOutOfRangeReturnsCode10(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);

        $this
            ->getJson("/rest/setRating.view?apiKey={$user->subsonic_api_key}" . "&f=json&id={$song->id}&rating=9")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/setRating.view?apiKey={$user->subsonic_api_key}" . '&f=json&id=does-not-exist&rating=3')
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function updatingExistingRatingOverwrites(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);
        Rating::factory()->createOne([
            'user_id' => $user->id,
            'rateable_type' => $song->getMorphClass(),
            'rateable_id' => $song->id,
            'rating' => 2,
        ]);

        $this->getJson(
            "/rest/setRating.view?apiKey={$user->subsonic_api_key}" . "&f=json&id={$song->id}&rating=5",
        )->assertOk();

        self::assertSame(5, $song->getRatingFor($user));
    }
}
