<?php

namespace Tests\Feature\KoelPlus;

use App\Models\PlaylistFolder;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistFolderTest extends PlusTestCase
{
    #[Test]
    public function collaboratorPuttingPlaylistIntoTheirFolder(): void
    {
        $collaborator = create_user();

        $playlist = create_playlist();
        $playlist->addCollaborator($collaborator);

        /** @var PlaylistFolder $ownerFolder */
        $ownerFolder = PlaylistFolder::factory()->for($playlist->owner)->create();
        $ownerFolder->playlists()->attach($playlist);
        self::assertTrue($playlist->refresh()->getFolder($playlist->owner)?->is($ownerFolder));

        /** @var PlaylistFolder $collaboratorFolder */
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->create();
        self::assertNull($playlist->getFolder($collaborator));

        $this->postAs(
            "api/playlist-folders/{$collaboratorFolder->id}/playlists",
            ['playlists' => [$playlist->id]],
            $collaborator
        )
            ->assertSuccessful();

        self::assertTrue($playlist->fresh()->getFolder($collaborator)?->is($collaboratorFolder));

        // Verify the playlist is in the owner's folder too
        self::assertTrue($playlist->fresh()->getFolder($playlist->owner)?->is($ownerFolder));
    }

    #[Test]
    public function collaboratorMovingPlaylistToRootLevel(): void
    {
        $collaborator = create_user();
        $playlist = create_playlist();
        $playlist->addCollaborator($collaborator);
        self::assertNull($playlist->getFolder($playlist->owner));

        /** @var PlaylistFolder $ownerFolder */
        $ownerFolder = PlaylistFolder::factory()->for($playlist->owner)->create();
        $ownerFolder->playlists()->attach($playlist);
        self::assertTrue($playlist->refresh()->getFolder($playlist->owner)?->is($ownerFolder));

        /** @var PlaylistFolder $collaboratorFolder */
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->create();

        $collaboratorFolder->playlists()->attach($playlist);
        self::assertTrue($playlist->refresh()->getFolder($collaborator)?->is($collaboratorFolder));

        $this->deleteAs(
            "api/playlist-folders/{$collaboratorFolder->id}/playlists",
            ['playlists' => [$playlist->id]],
            $collaborator
        )
            ->assertSuccessful();

        self::assertNull($playlist->fresh()->getFolder($collaborator));
        // Verify the playlist is still in the owner's folder
        self::assertTrue($playlist->getFolder($playlist->owner)?->is($ownerFolder));
    }
}
