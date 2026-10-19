<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class CreatePlaylistTest extends PlusTestCase
{
    #[Test]
    public function refuseSongsTheUserCannotAccess(): void
    {
        $user = create_user();
        $someoneElsesPrivateSong = Song::factory()->private()->createOne();

        $this->getJson(
            "/rest/createPlaylist.view?apiKey={$user->subsonic_api_key}"
            . "&f=json&name=Mix&songId={$someoneElsesPrivateSong->id}",
        )->assertJsonPath('subsonic-response.status', 'failed');

        self::assertSame(0, $user->ownedPlaylists()->count());
    }
}
