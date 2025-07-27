<?php

namespace Tests\Feature\KoelPlus;

use App\Events\MultipleSongsLiked;
use App\Events\MultipleSongsUnliked;
use App\Events\SongFavoriteToggled;
use App\Models\Song;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class InteractionTest extends PlusTestCase
{
    #[Test]
    public function policyForRegisterPlay(): void
    {
        Event::fake(SongFavoriteToggled::class);

        $owner = create_user();

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

    #[Test]
    public function policyForToggleLike(): void
    {
        Event::fake(SongFavoriteToggled::class);

        $owner = create_user();

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

    #[Test]
    public function policyForBatchLike(): void
    {
        Event::fake(MultipleSongsLiked::class);

        $owner = create_user();

        // Can't batch like private songs that don't belong to the user
        $externalPrivateSongs = Song::factory()->count(2)->private()->create();
        $this->postAs('api/interaction/batch/like', ['songs' => $externalPrivateSongs->modelKeys()], $owner)
            ->assertForbidden();

        // Can batch like public songs that don't belong to the user
        $externalPublicSongs = Song::factory()->count(1)->public()->create();
        $this->postAs('api/interaction/batch/like', ['songs' => $externalPublicSongs->modelKeys()], $owner)
            ->assertSuccessful();

        // Can batch like private songs that belong to the user
        $ownPrivateSongs = Song::factory()->count(2)->private()->for($owner, 'owner')->create();
        $this->postAs('api/interaction/batch/like', ['songs' => $ownPrivateSongs->modelKeys()], $owner)
            ->assertSuccessful();

        // Can't batch like a mix of inaccessible and accessible songs
        $mixedSongs = $externalPrivateSongs->merge($externalPublicSongs);
        $this->postAs('api/interaction/batch/like', ['songs' => $mixedSongs->modelKeys()], $owner)
            ->assertForbidden();
    }

    #[Test]
    public function policyForBatchUnlike(): void
    {
        Event::fake(MultipleSongsUnliked::class);

        $owner = create_user();

        // Can't batch unlike private songs that don't belong to the user
        $externalPrivateSongs = Song::factory()->count(2)->private()->create();
        $this->postAs('api/interaction/batch/unlike', ['songs' => $externalPrivateSongs->modelKeys()], $owner)
            ->assertForbidden();

        // Can batch unlike public songs that don't belong to the user
        $externalPublicSongs = Song::factory()->count(1)->public()->create();
        $this->postAs('api/interaction/batch/unlike', ['songs' => $externalPublicSongs->modelKeys()], $owner)
            ->assertSuccessful();

        // Can batch unlike private songs that belong to the user
        $ownPrivateSongs = Song::factory()->count(2)->private()->for($owner, 'owner')->create();
        $this->postAs('api/interaction/batch/unlike', ['songs' => $ownPrivateSongs->modelKeys()], $owner)
            ->assertSuccessful();

        // Can't batch unlike a mix of inaccessible and accessible songs
        $mixedSongs = $externalPrivateSongs->merge($externalPublicSongs);
        $this->postAs('api/interaction/batch/unlike', ['songs' => $mixedSongs->modelKeys()], $owner)
            ->assertForbidden();
    }
}
