<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Interaction;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class ScrobbleTest extends PlusTestCase
{
    #[Test]
    public function silentlySkipsUnaccessibleSongs(): void
    {
        $owner = create_user();
        $owner->preferences->includePublicMedia = false;
        $owner->save();

        $requester = create_user();
        $requester->preferences->includePublicMedia = false;
        $requester->save();

        $foreignSong = Song::factory()->createOne(['owner_id' => $owner->id]);
        $ownSong = Song::factory()->createOne(['owner_id' => $requester->id]);

        $this
            ->getJson(
                "/rest/scrobble.view?apiKey={$requester->subsonic_api_key}"
                . "&f=json&id={$foreignSong->id}&id={$ownSong->id}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertNull(
            Interaction::query()->where('user_id', $requester->id)->where('song_id', $foreignSong->id)->first(),
        );
        self::assertSame(
            1,
            (int) Interaction::query()
                ->where('user_id', $requester->id)
                ->where('song_id', $ownSong->id)
                ->value('play_count'),
        );
    }
}
