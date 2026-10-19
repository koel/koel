<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Models\User;
use App\Services\LibrarySummaryService;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlists;
use function Tests\create_user;

class SongVisibilityTest extends PlusTestCase
{
    #[Test]
    public function makingSongPublic(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalSongs = Song::factory()->for($anotherUser, 'owner')->private()->createMany(2);

        // We can't make public songs that are not ours.
        $this->putAs('api/songs/publicize', ['songs' => $externalSongs->modelKeys()], $currentUser)->assertForbidden();

        // But we can our own songs.
        $ownSongs = Song::factory()->for($currentUser, 'owner')->private()->createMany(2);

        $this->putAs('api/songs/publicize', ['songs' => $ownSongs->modelKeys()], $currentUser)->assertSuccessful();

        $ownSongs->each(static fn (Song $song) => self::assertTrue($song->refresh()->is_public));
    }

    #[Test]
    public function makingSongPrivate(): void
    {
        $currentUser = create_user();
        $anotherUser = create_user();

        $externalSongs = Song::factory()->for($anotherUser, 'owner')->public()->createMany(2);

        // We can't Mark as Private songs that are not ours.
        $this->putAs('api/songs/privatize', ['songs' => $externalSongs->modelKeys()], $currentUser)->assertForbidden();

        // But we can our own songs.
        $ownSongs = Song::factory()->for($currentUser, 'owner')->createMany(2);

        $this->putAs('api/songs/privatize', ['songs' => $ownSongs->modelKeys()], $currentUser)->assertSuccessful();

        $ownSongs->each(static fn (Song $song) => self::assertFalse($song->refresh()->is_public));
    }

    #[Test]
    public function keepOnlySongsInCollaborativePlaylistsPublic(): void
    {
        $owner = create_user();
        [$soloSong, $sharedSong] = Song::factory()->for($owner, 'owner')->public()->createMany(2)->all();
        [$soloPlaylist, $sharedPlaylist] = create_playlists(2, owner: $owner)->all();
        $soloPlaylist->addPlayables($soloSong);
        $sharedPlaylist->addPlayables($sharedSong);
        $sharedPlaylist->addCollaborator(create_user());

        $this->putAs('api/songs/privatize', ['songs' => [$soloSong->id, $sharedSong->id]], $owner)->assertJson([
            $soloSong->id,
        ]);

        self::assertFalse($soloSong->refresh()->is_public);
        self::assertTrue($sharedSong->refresh()->is_public);
    }

    #[Test]
    public function makingSongsPublicRefreshesTheSongCountOfOtherUsers(): void
    {
        $owner = create_user();
        $viewer = self::createUserIncludingPublicMedia();
        $songs = Song::factory()->for($owner, 'owner')->private()->createMany(2);
        $librarySummaryService = app(LibrarySummaryService::class);

        self::assertSame(0, $librarySummaryService->getSongCount($viewer));

        $this->putAs('api/songs/publicize', ['songs' => $songs->modelKeys()], $owner)->assertSuccessful();

        self::assertSame(2, $librarySummaryService->getSongCount($viewer));
    }

    #[Test]
    public function makingSongsPrivateRefreshesTheSongCountOfOtherUsers(): void
    {
        $owner = create_user();
        $viewer = self::createUserIncludingPublicMedia();
        $songs = Song::factory()->for($owner, 'owner')->public()->createMany(2);
        $librarySummaryService = app(LibrarySummaryService::class);

        self::assertSame(2, $librarySummaryService->getSongCount($viewer));

        $this->putAs('api/songs/privatize', ['songs' => $songs->modelKeys()], $owner)->assertSuccessful();

        self::assertSame(0, $librarySummaryService->getSongCount($viewer));
    }

    private static function createUserIncludingPublicMedia(): User
    {
        $user = create_user();
        $user->preferences->includePublicMedia = true;
        $user->save();

        return $user;
    }
}
