<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;

class SongTest extends PlusTestCase
{
    #[Test]
    public function showSongPolicy(): void
    {
        $user = create_user();
        $publicSong = Song::factory()->public()->createOne();

        // We can access public songs.
        $this->getAs("api/songs/{$publicSong->id}", $user)->assertSuccessful();
        $ownPrivateSong = Song::factory()
            ->for($user, 'owner')
            ->private()
            ->createOne();

        // We can access our own private songs.
        $this->getAs("api/songs/{$ownPrivateSong->id}", $user)->assertSuccessful();
        $externalUnownedSong = Song::factory()->private()->createOne();

        // But we can't access private songs that are not ours.
        $this->getAs("api/songs/{$externalUnownedSong->id}", $user)->assertForbidden();
    }

    #[Test]
    public function editSongsPolicy(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalUnownedSongs = Song::factory()
            ->for($anotherUser, 'owner')
            ->private()
            ->createMany(2);

        // We can't edit songs that are not ours.
        $this->putAs(
            'api/songs',
            [
                'songs' => $externalUnownedSongs->modelKeys(),
                'data' => [
                    'title' => 'New Title',
                ],
            ],
            $currentUser,
        )->assertForbidden();

        // Even if some of the songs are owned by us, we still can't edit them.
        $mixedSongs = $externalUnownedSongs->merge(Song::factory()->for($currentUser, 'owner')->createMany(2));

        $this->putAs(
            'api/songs',
            [
                'songs' => $mixedSongs->modelKeys(),
                'data' => [
                    'title' => 'New Title',
                ],
            ],
            $currentUser,
        )->assertForbidden();

        // But we can edit our own songs.
        $ownSongs = Song::factory()->for($currentUser, 'owner')->createMany(2);

        $this->putAs(
            'api/songs',
            [
                'songs' => $ownSongs->modelKeys(),
                'data' => [
                    'title' => 'New Title',
                ],
            ],
            $currentUser,
        )->assertSuccessful();
    }

    #[Test]
    public function deleteSongsPolicy(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalUnownedSongs = Song::factory()
            ->for($anotherUser, 'owner')
            ->private()
            ->createMany(2);

        // We can't delete songs that are not ours.
        $this->deleteAs('api/songs', ['songs' => $externalUnownedSongs->modelKeys()], $currentUser)->assertForbidden();

        // Even if some of the songs are owned by us, we still can't delete them.
        $mixedSongs = $externalUnownedSongs->merge(Song::factory()->for($currentUser, 'owner')->createMany(2));

        $this->deleteAs('api/songs', ['songs' => $mixedSongs->modelKeys()], $currentUser)->assertForbidden();

        // But we can delete our own songs.
        $ownSongs = Song::factory()->for($currentUser, 'owner')->createMany(2);

        $this->deleteAs('api/songs', ['songs' => $ownSongs->modelKeys()], $currentUser)->assertSuccessful();
    }

    #[Test]
    public function markSongsAsPublic(): void
    {
        $user = create_user();

        $songs = Song::factory()
            ->for($user, 'owner')
            ->private()
            ->createMany(2);

        $this->putAs('api/songs/publicize', ['songs' => $songs->modelKeys()], $user)->assertSuccessful();

        $songs->each(static function (Song $song): void {
            $song->refresh();
            self::assertTrue($song->is_public);
        });
    }

    #[Test]
    public function markSongsAsPrivate(): void
    {
        $user = create_user();

        $songs = Song::factory()
            ->for($user, 'owner')
            ->public()
            ->createMany(2);

        $this->putAs('api/songs/privatize', ['songs' => $songs->modelKeys()], $user)->assertSuccessful();

        $songs->each(static function (Song $song): void {
            $song->refresh();
            self::assertFalse($song->is_public);
        });
    }

    #[Test]
    public function publicizingOrPrivatizingSongsRequiresOwnership(): void
    {
        $songs = Song::factory()->public()->createMany(2);

        $this->putAs('api/songs/privatize', ['songs' => $songs->modelKeys()])->assertForbidden();

        $otherSongs = Song::factory()->private()->createMany(2);

        $this->putAs('api/songs/publicize', ['songs' => $otherSongs->modelKeys()])->assertForbidden();
    }
}
