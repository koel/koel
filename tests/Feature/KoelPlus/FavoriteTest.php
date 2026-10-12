<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class FavoriteTest extends PlusTestCase
{
    #[Test]
    public function toggleIsProhibitedIfSongIsNotAccessible(): void
    {
        $song = Song::factory()->private()->createOne();

        $this->postAs('api/favorites/toggle', [
            'type' => 'playable',
            'id' => $song->id,
        ])->assertForbidden();
    }

    #[Test]
    public function toggleIsProhibitedIfAlbumIsNotAccessible(): void
    {
        $album = Album::factory()->createOne();

        $this->postAs('api/favorites/toggle', [
            'type' => 'album',
            'id' => $album->id,
        ])->assertForbidden();
    }

    #[Test]
    public function toggleIsProhibitedIfArtistIsNotAccessible(): void
    {
        $artist = Artist::factory()->createOne();

        $this->postAs('api/favorites/toggle', [
            'type' => 'artist',
            'id' => $artist->id,
        ])->assertForbidden();
    }

    #[Test]
    public function toggleAlbumWithAnotherUsersPublicSong(): void
    {
        $owner = create_user();
        $album = Album::factory()->for($owner)->createOne();

        Song::factory()
            ->for($album)
            ->for($album->artist)
            ->for($owner, 'owner')
            ->public()
            ->createOne();

        $this->postAs(
            'api/favorites/toggle',
            [
                'type' => 'album',
                'id' => $album->id,
            ],
            self::createUserIncludingPublicMedia(),
        )->assertCreated();
    }

    #[Test]
    public function toggleArtistWithAnotherUsersPublicSong(): void
    {
        $owner = create_user();
        $artist = Artist::factory()->for($owner)->createOne();

        Song::factory()->for($artist)->for($owner, 'owner')->public()->createOne();

        $this->postAs(
            'api/favorites/toggle',
            [
                'type' => 'artist',
                'id' => $artist->id,
            ],
            self::createUserIncludingPublicMedia(),
        )->assertCreated();
    }

    #[Test]
    public function batchFavoriteIsProhibitedIfAnySongIsNotAccessible(): void
    {
        $songs = Song::factory()->createMany(2);
        $songs->first()->update(['is_public' => false]);

        $this->postAs('api/favorites', [
            'type' => 'playable',
            'ids' => $songs->pluck('id')->toArray(),
        ])->assertForbidden();
    }

    #[Test]
    public function batchUndoFavoriteIsProhibitedIfAnySongIsNotAccessible(): void
    {
        $songs = Song::factory()->createMany(2);
        $songs->first()->update(['is_public' => false]);

        $this->deleteAs('api/favorites', [
            'type' => 'playable',
            'ids' => $songs->pluck('id')->toArray(),
        ])->assertForbidden();
    }

    private static function createUserIncludingPublicMedia(): User
    {
        $user = create_user();
        $user->preferences->includePublicMedia = true;
        $user->save();

        return $user;
    }
}
