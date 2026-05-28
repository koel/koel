<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class StarTest extends PlusTestCase
{
    #[Test]
    public function skipsUnaccessibleAlbumsAndArtists(): void
    {
        $owner = create_user();
        $owner->preferences->includePublicMedia = false;
        $owner->save();

        $requester = create_user();
        $requester->preferences->includePublicMedia = false;
        $requester->save();

        $foreignAlbum = Album::factory()->createOne(['user_id' => $owner->id]);
        $foreignArtist = Artist::factory()->createOne(['user_id' => $owner->id]);

        $this
            ->getJson(
                "/rest/star.view?apiKey={$requester->subsonic_api_key}"
                . "&f=json&albumId={$foreignAlbum->id}&artistId={$foreignArtist->id}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertSame(0, Favorite::query()->where('user_id', $requester->id)->count());
    }
}
