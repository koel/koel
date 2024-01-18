<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use Illuminate\Support\Collection;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongTest extends PlusTestCase
{
    public function testWithOwnSongsOnlyOptionOn(): void
    {
        $user = create_user();

        Song::factory(2)->public()->create();
        $ownSongs = Song::factory(3)->for($user, 'owner')->create();

        $this->getAs('api/songs?own_songs_only=true', $user)
            ->assertSuccessful()
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment(['id' => $ownSongs[0]->id])
            ->assertJsonFragment(['id' => $ownSongs[1]->id])
            ->assertJsonFragment(['id' => $ownSongs[2]->id]);
    }

    public function testWithOwnSongsOnlyOptionOffOrMissing(): void
    {
        $user = create_user();

        Song::factory(2)->public()->create();
        Song::factory(3)->for($user, 'owner')->create();

        $this->getAs('api/songs?own_songs_only=false', $user)
            ->assertSuccessful()
            ->assertJsonCount(5, 'data');

        $this->getAs('api/songs', $user)
            ->assertSuccessful()
            ->assertJsonCount(5, 'data');
    }

    public function testShowSongPolicy(): void
    {
        $user = create_user();

        /** @var Song $publicSong */
        $publicSong = Song::factory()->public()->create();

        // We can access public songs.
        $this->getAs("api/songs/$publicSong->id", $user)->assertSuccessful();

        /** @var Song $ownPrivateSong */
        $ownPrivateSong = Song::factory()->for($user, 'owner')->private()->create();

        // We can access our own private songs.
        $this->getAs('api/songs/' . $ownPrivateSong->id, $user)->assertSuccessful();

        /** @var Song $externalUnownedSong */
        $externalUnownedSong = Song::factory()->private()->create();

        // But we can't access private songs that are not ours.
        $this->getAs("api/songs/$externalUnownedSong->id", $user)->assertForbidden();
    }

    public function testEditSongsPolicy(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        /** @var Collection<Song> $externalUnownedSongs */
        $externalUnownedSongs = Song::factory(3)->for($anotherUser, 'owner')->private()->create();

        // We can't edit songs that are not ours.
        $this->putAs('api/songs', [
            'songs' => $externalUnownedSongs->pluck('id')->toArray(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertForbidden();

        // Even if some of the songs are owned by us, we still can't edit them.
        $mixedSongs = $externalUnownedSongs->merge(Song::factory(2)->for($currentUser, 'owner')->create());

        $this->putAs('api/songs', [
            'songs' => $mixedSongs->pluck('id')->toArray(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertForbidden();

        // But we can edit our own songs.
        $ownSongs = Song::factory(3)->for($currentUser, 'owner')->create();

        $this->putAs('api/songs', [
            'songs' => $ownSongs->pluck('id')->toArray(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertSuccessful();
    }

    public function testDeleteSongsPolicy(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        /** @var Collection<Song> $externalUnownedSongs */
        $externalUnownedSongs = Song::factory(3)->for($anotherUser, 'owner')->private()->create();

        // We can't delete songs that are not ours.
        $this->deleteAs('api/songs', ['songs' => $externalUnownedSongs->pluck('id')->toArray()], $currentUser)
            ->assertForbidden();

        // Even if some of the songs are owned by us, we still can't delete them.
        $mixedSongs = $externalUnownedSongs->merge(Song::factory(2)->for($currentUser, 'owner')->create());

        $this->deleteAs('api/songs', ['songs' => $mixedSongs->pluck('id')->toArray()], $currentUser)
            ->assertForbidden();

        // But we can delete our own songs.
        $ownSongs = Song::factory(3)->for($currentUser, 'owner')->create();

        $this->deleteAs('api/songs', ['songs' => $ownSongs->pluck('id')->toArray()], $currentUser)
            ->assertSuccessful();
    }

    public function testPublicizeSongs(): void
    {
        $user = create_user();

        /** @var Song $songs */
        $songs = Song::factory(3)->for($user, 'owner')->private()->create();

        $this->putAs('api/songs/publicize', ['songs' => $songs->pluck('id')->toArray()], $user)
            ->assertSuccessful();

        $songs->each(static function (Song $song): void {
            $song->refresh();
            self::assertTrue($song->is_public);
        });
    }

    public function testPrivatizeSongs(): void
    {
        $user = create_user();

        /** @var Song $songs */
        $songs = Song::factory(3)->for($user, 'owner')->public()->create();

        $this->putAs('api/songs/privatize', ['songs' => $songs->pluck('id')->toArray()], $user)
            ->assertSuccessful();

        $songs->each(static function (Song $song): void {
            $song->refresh();
            self::assertFalse($song->is_public);
        });
    }

    public function testPublicizingOrPrivatizingSongsRequiresOwnership(): void
    {
        $songs = Song::factory(3)->public()->create();

        $this->putAs('api/songs/privatize', ['songs' => $songs->pluck('id')->toArray()])
            ->assertForbidden();

        $otherSongs = Song::factory(3)->private()->create();

        $this->putAs('api/songs/publicize', ['songs' => $otherSongs->pluck('id')->toArray()])
            ->assertForbidden();
    }
}
