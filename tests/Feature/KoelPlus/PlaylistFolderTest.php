<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Playlist;
use App\Models\PlaylistFolder;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistFolderTest extends PlusTestCase
{
    public function testCollaboratorPuttingPlaylistIntoTheirFolder(): void
    {
        $collaborator = create_user();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addCollaborator($collaborator);

        /** @var PlaylistFolder $ownerFolder */
        $ownerFolder = PlaylistFolder::factory()->for($playlist->user)->create();
        $ownerFolder->playlists()->attach($playlist->id);
        self::assertTrue($playlist->refresh()->getFolder($playlist->user)?->is($ownerFolder));

        /** @var PlaylistFolder $collaboratorFolder */
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->create();
        self::assertNull($playlist->getFolder($collaborator));

        $this->postAs(
            "api/playlist-folders/$collaboratorFolder->id/playlists",
            ['playlists' => [$playlist->id]],
            $collaborator
        )
            ->assertSuccessful();

        self::assertTrue($playlist->fresh()->getFolder($collaborator)?->is($collaboratorFolder));

        // Verify the playlist is in the owner's folder too
        self::assertTrue($playlist->fresh()->getFolder($playlist->user)?->is($ownerFolder));
    }

    public function testCollaboratorMovingPlaylistToRootLevel(): void
    {
        $collaborator = create_user();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $playlist->addCollaborator($collaborator);
        self::assertNull($playlist->getFolder($playlist->user));

        /** @var PlaylistFolder $ownerFolder */
        $ownerFolder = PlaylistFolder::factory()->for($playlist->user)->create();
        $ownerFolder->playlists()->attach($playlist->id);
        self::assertTrue($playlist->refresh()->getFolder($playlist->user)?->is($ownerFolder));

        /** @var PlaylistFolder $collaboratorFolder */
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->create();

        $collaboratorFolder->playlists()->attach($playlist->id);
        self::assertTrue($playlist->refresh()->getFolder($collaborator)?->is($collaboratorFolder));

        $this->deleteAs(
            "api/playlist-folders/$collaboratorFolder->id/playlists",
            ['playlists' => [$playlist->id]],
            $collaborator
        )
            ->assertSuccessful();

        self::assertNull($playlist->fresh()->getFolder($collaborator));
        // Verify the playlist is still in the owner's folder
        self::assertTrue($playlist->getFolder($playlist->user)?->is($ownerFolder));
    }
}
