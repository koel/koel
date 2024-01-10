<?php

namespace Tests\Feature\KoelPlus;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;
use Tests\TestCase;

class InteractionTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
    }

    public function testPolicyForRegisterPlay(): void
    {
        $this->withoutEvents();

        /** @var User $owner */
        $owner = User::factory()->create();

        // Can't increase play count of a private song that doesn't belong to the user
        /** @var Song $externalPrivateSong */
        $externalPrivateSong = Song::factory()->private()->create();
        $this->postAs('api/interaction/play', ['song' => $externalPrivateSong->id], $owner)
            ->assertForbidden();

        // Can increase play count of a public song that doesn't belong to the user
        /** @var Song $externalPublicSong */
        $externalPublicSong = Song::factory()->public()->create();
        $this->postAs('api/interaction/play', ['song' => $externalPublicSong->id], $owner)
            ->assertSuccessful();

        // Can increase play count of a private song that belongs to the user
        /** @var Song $ownPrivateSong */
        $ownPrivateSong = Song::factory()->private()->for($owner, 'owner')->create();
        $this->postAs('api/interaction/play', ['song' => $ownPrivateSong->id], $ownPrivateSong->owner)
            ->assertSuccessful();
    }

    public function testPolicyForToggleLike(): void
    {
        $this->withoutEvents();

        /** @var User $user */
        $owner = User::factory()->create();

        // Can't like a private song that doesn't belong to the user
        /** @var Song $externalPrivateSong */
        $externalPrivateSong = Song::factory()->private()->create();
        $this->postAs('api/interaction/like', ['song' => $externalPrivateSong->id], $owner)
            ->assertForbidden();

        // Can like a public song that doesn't belong to the user
        /** @var Song $externalPublicSong */
        $externalPublicSong = Song::factory()->public()->create();
        $this->postAs('api/interaction/like', ['song' => $externalPublicSong->id], $owner)
            ->assertSuccessful();

        // Can like a private song that belongs to the user
        /** @var Song $ownPrivateSong */
        $ownPrivateSong = Song::factory()->private()->for($owner, 'owner')->create();
        $this->postAs('api/interaction/like', ['song' => $ownPrivateSong->id], $owner)
            ->assertSuccessful();
    }

    public function testPolicyForBatchLike(): void
    {
        $this->withoutEvents();

        /** @var User $user */
        $owner = User::factory()->create();

        // Can't batch like private songs that don't belong to the user
        /** @var Collection $externalPrivateSongs */
        $externalPrivateSongs = Song::factory()->count(3)->private()->create();
        $this->postAs('api/interaction/batch/like', ['songs' => $externalPrivateSongs->pluck('id')->all()], $owner)
            ->assertForbidden();

        // Can batch like public songs that don't belong to the user
        /** @var Collection $externalPublicSongs */
        $externalPublicSongs = Song::factory()->count(3)->public()->create();
        $this->postAs('api/interaction/batch/like', ['songs' => $externalPublicSongs->pluck('id')->all()], $owner)
            ->assertSuccessful();

        // Can batch like private songs that belong to the user
        /** @var Collection $ownPrivateSongs */
        $ownPrivateSongs = Song::factory()->count(3)->private()->for($owner, 'owner')->create();
        $this->postAs('api/interaction/batch/like', ['songs' => $ownPrivateSongs->pluck('id')->all()], $owner)
            ->assertSuccessful();

        // Can't batch like a mix of inaccessible and accessible songs
        $mixedSongs = $externalPrivateSongs->merge($externalPublicSongs);
        $this->postAs('api/interaction/batch/like', ['songs' => $mixedSongs->pluck('id')->all()], $owner)
            ->assertForbidden();
    }

    public function testPolicyForBatchUnlike(): void
    {
        $this->withoutEvents();

        /** @var User $user */
        $owner = User::factory()->create();

        // Can't batch unlike private songs that don't belong to the user
        /** @var Collection $externalPrivateSongs */
        $externalPrivateSongs = Song::factory()->count(3)->private()->create();
        $this->postAs('api/interaction/batch/unlike', ['songs' => $externalPrivateSongs->pluck('id')->all()], $owner)
            ->assertForbidden();

        // Can batch unlike public songs that don't belong to the user
        /** @var Collection $externalPublicSongs */
        $externalPublicSongs = Song::factory()->count(3)->public()->create();
        $this->postAs('api/interaction/batch/unlike', ['songs' => $externalPublicSongs->pluck('id')->all()], $owner)
            ->assertSuccessful();

        // Can batch unlike private songs that belong to the user
        /** @var Collection $ownPrivateSongs */
        $ownPrivateSongs = Song::factory()->count(3)->private()->for($owner, 'owner')->create();
        $this->postAs('api/interaction/batch/unlike', ['songs' => $ownPrivateSongs->pluck('id')->all()], $owner)
            ->assertSuccessful();

        // Can't batch unlike a mix of inaccessible and accessible songs
        $mixedSongs = $externalPrivateSongs->merge($externalPublicSongs);
        $this->postAs('api/interaction/batch/unlike', ['songs' => $mixedSongs->pluck('id')->all()], $owner)
            ->assertForbidden();
    }
}
