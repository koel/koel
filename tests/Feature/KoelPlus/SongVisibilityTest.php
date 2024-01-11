<?php

namespace Tests\Feature\KoelPlus;

use App\Facades\License;
use App\Models\Song;
use Illuminate\Support\Collection;
use Tests\TestCase;

use function Tests\create_user;

class SongVisibilityTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
    }

    public function testMakingSongPublic(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        /** @var Collection<Song> $externalSongs */
        $externalSongs = Song::factory(3)->for($anotherUser, 'owner')->private()->create();

        // We can't make public songs that are not ours.
        $this->putAs('api/songs/make-public', ['songs' => $externalSongs->pluck('id')->toArray()], $currentUser)
            ->assertForbidden();

        // But we can our own songs.
        $ownSongs = Song::factory(3)->for($currentUser, 'owner')->create();

        $this->putAs('api/songs/make-public', ['songs' => $ownSongs->pluck('id')->toArray()], $currentUser)
            ->assertSuccessful();

        $ownSongs->each(static fn (Song $song) => self::assertTrue($song->refresh()->is_public));
    }

    public function testMakingSongPrivate(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        /** @var Collection<Song> $externalSongs */
        $externalSongs = Song::factory(3)->for($anotherUser, 'owner')->public()->create();

        // We can't make private songs that are not ours.
        $this->putAs('api/songs/make-private', ['songs' => $externalSongs->pluck('id')->toArray()], $currentUser)
            ->assertForbidden();

        // But we can our own songs.
        $ownSongs = Song::factory(3)->for($currentUser, 'owner')->create();

        $this->putAs('api/songs/make-private', ['songs' => $ownSongs->pluck('id')->toArray()], $currentUser)
            ->assertSuccessful();

        $ownSongs->each(static fn (Song $song) => self::assertFalse($song->refresh()->is_public));
    }
}
