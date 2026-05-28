<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Rating;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class SetRatingTest extends PlusTestCase
{
    #[Test]
    public function rejectsAnotherUsersEntity(): void
    {
        $owner = create_user();
        $owner->preferences->includePublicMedia = false;
        $owner->save();

        $requester = create_user();
        $requester->preferences->includePublicMedia = false;
        $requester->save();

        $song = Song::factory()->createOne(['owner_id' => $owner->id]);

        $this
            ->getJson("/rest/setRating.view?apiKey={$requester->subsonic_api_key}" . "&f=json&id={$song->id}&rating=5")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);

        self::assertSame(0, Rating::query()->where('user_id', $requester->id)->count());
    }
}
