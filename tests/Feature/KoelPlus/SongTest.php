<?php

namespace Tests\Feature\KoelPlus;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class SongTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
    }

    public function testShowSongPolicy(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var Song $publicSong */
        $publicSong = Song::factory()->public()->create();

        // We can access public songs.
        $this->getAs('api/songs/' . $publicSong->id, $user)->assertSuccessful();

        /** @var Song $ownPrivateSong */
        $ownPrivateSong = Song::factory()->for($user, 'owner')->private()->create();

        // We can access our own private songs.
        $this->getAs('api/songs/' . $ownPrivateSong->id, $user)->assertSuccessful();

        $externalPrivateSong = Song::factory()->private()->create();

        // But we can't access private songs that are not ours.
        $this->getAs('api/songs/' . $externalPrivateSong->id, $user)->assertForbidden();
    }

    public function testEditSongsPolicy(): void
    {
        /** @var User $currentUser */
        $currentUser = User::factory()->create();

        /** @var User $anotherUser */
        $anotherUser = User::factory()->create();

        /** @var Collection<Song> $externalSongs */
        $externalSongs = Song::factory(3)->for($anotherUser, 'owner')->private()->create();

        // We can't edit songs that are not ours.
        $this->putAs('api/songs', [
            'songs' => $externalSongs->pluck('id')->toArray(),
            'data' => [
                'title' => 'New Title',
            ],
        ], $currentUser)->assertForbidden();

        // Even if some of the songs are owned by us, we still can't edit them.
        $mixedSongs = $externalSongs->merge(Song::factory(2)->for($currentUser, 'owner')->create());

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
        /** @var User $currentUser */
        $currentUser = User::factory()->create();

        /** @var User $anotherUser */
        $anotherUser = User::factory()->create();

        /** @var Collection<Song> $externalSongs */
        $externalSongs = Song::factory(3)->for($anotherUser, 'owner')->private()->create();

        // We can't delete songs that are not ours.
        $this->deleteAs('api/songs', ['songs' => $externalSongs->pluck('id')->toArray()], $currentUser)
            ->assertForbidden();

        // Even if some of the songs are owned by us, we still can't delete them.
        $mixedSongs = $externalSongs->merge(Song::factory(2)->for($currentUser, 'owner')->create());

        $this->deleteAs('api/songs', ['songs' => $mixedSongs->pluck('id')->toArray()], $currentUser)
            ->assertForbidden();

        // But we can delete our own songs.
        $ownSongs = Song::factory(3)->for($currentUser, 'owner')->create();

        $this->deleteAs('api/songs', ['songs' => $ownSongs->pluck('id')->toArray()], $currentUser)
            ->assertSuccessful();
    }
}
