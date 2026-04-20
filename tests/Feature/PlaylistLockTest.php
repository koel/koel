<?php

namespace Tests\Feature;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistLockTest extends TestCase
{
    #[Test]
    public function ownerCanLockPlaylist(): void
    {
        $playlist = create_playlist();

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => $playlist->name,
                'is_locked' => true,
            ],
            $playlist->owner,
        )->assertSuccessful();

        self::assertTrue($playlist->refresh()->is_locked);
    }

    #[Test]
    public function ownerCanUnlockPlaylist(): void
    {
        $playlist = create_playlist(['is_locked' => true]);

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => $playlist->name,
                'is_locked' => false,
            ],
            $playlist->owner,
        )->assertSuccessful();

        self::assertFalse($playlist->refresh()->is_locked);
    }

    #[Test]
    public function nonOwnerCannotLockOrUnlockPlaylist(): void
    {
        $playlist = create_playlist();
        $otherUser = create_user();

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => 'Changed',
                'is_locked' => true,
            ],
            $otherUser,
        )->assertForbidden();

        self::assertFalse($playlist->refresh()->is_locked);
    }

    #[Test]
    public function lockedPlaylistCannotHaveSongsAdded(): void
    {
        $playlist = create_playlist(['is_locked' => true]);
        $song = Song::factory()->createOne();

        $this->postAs(
            "api/playlists/{$playlist->id}/songs",
            [
                'songs' => [$song->id],
            ],
            $playlist->owner,
        )->assertForbidden();

        self::assertCount(0, $playlist->playables);
    }

    #[Test]
    public function lockedPlaylistCannotHaveSongsRemoved(): void
    {
        $playlist = create_playlist(['is_locked' => true]);
        $song = Song::factory()->createOne();
        $playlist->is_locked = false;
        $playlist->save();
        $playlist->addPlayables([$song->id]);
        $playlist->is_locked = true;
        $playlist->save();

        $this->deleteAs(
            "api/playlists/{$playlist->id}/songs",
            [
                'songs' => [$song->id],
            ],
            $playlist->owner,
        )->assertForbidden();

        self::assertCount(1, $playlist->refresh()->playables);
    }

    #[Test]
    public function collaboratorCannotAddSongsToLockedPlaylist(): void
    {
        $owner = create_user();
        $collaborator = create_user();
        $playlist = create_playlist(['is_locked' => true]);
        $playlist->users()->detach();
        $playlist->users()->attach($owner, ['role' => 'owner']);
        $playlist->users()->attach($collaborator, ['role' => 'collaborator']);

        $song = Song::factory()->createOne();

        $this->postAs(
            "api/playlists/{$playlist->id}/songs",
            [
                'songs' => [$song->id],
            ],
            $collaborator,
        )->assertForbidden();
    }

    #[Test]
    public function deletingSongStillRemovesItFromLockedPlaylist(): void
    {
        $playlist = create_playlist(['is_locked' => true]);
        $song = Song::factory()->createOne();

        $playlist->is_locked = false;
        $playlist->save();
        $playlist->addPlayables([$song->id]);
        $playlist->is_locked = true;
        $playlist->save();

        self::assertCount(1, $playlist->refresh()->playables);

        $song->delete();

        self::assertCount(0, $playlist->refresh()->playables);
    }
}
