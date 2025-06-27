<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongVisibilityTest extends PlusTestCase
{
    #[Test]
    public function makingSongPublic(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalSongs = Song::factory(2)->for($anotherUser, 'owner')->private()->create();

        // We can't make public songs that are not ours.
        $this->putAs('api/songs/publicize', ['songs' => $externalSongs->modelKeys()], $currentUser)
            ->assertForbidden();

        // But we can our own songs.
        $ownSongs = Song::factory(2)->for($currentUser, 'owner')->create();

        $this->putAs('api/songs/publicize', ['songs' => $ownSongs->modelKeys()], $currentUser)
            ->assertSuccessful();

        $ownSongs->each(static fn (Song $song) => self::assertTrue($song->refresh()->is_public));
    }

    #[Test]
    public function makingSongPrivate(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalSongs = Song::factory(2)->for($anotherUser, 'owner')->public()->create();

        // We can't Mark as Private songs that are not ours.
        $this->putAs('api/songs/privatize', ['songs' => $externalSongs->modelKeys()], $currentUser)
            ->assertForbidden();

        // But we can our own songs.
        $ownSongs = Song::factory(2)->for($currentUser, 'owner')->create();

        $this->putAs('api/songs/privatize', ['songs' => $ownSongs->modelKeys()], $currentUser)
            ->assertSuccessful();

        $ownSongs->each(static fn (Song $song) => self::assertFalse($song->refresh()->is_public));
    }
}
