<?php

namespace Tests\Feature\KoelPlus\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
use App\Models\Interaction;
use App\Models\Rating;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class AccessGuardsTest extends PlusTestCase
{
    #[Test]
    public function setRatingRejectsAnotherUsersEntity(): void
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

    #[Test]
    public function scrobbleSilentlySkipsUnaccessibleSongs(): void
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

    #[Test]
    public function starSkipsUnaccessibleAlbumsAndArtists(): void
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
