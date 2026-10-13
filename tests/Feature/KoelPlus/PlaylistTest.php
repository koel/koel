<?php

namespace Tests\Feature\KoelPlus;

use App\Models\PlaylistFolder;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistTest extends PlusTestCase
{
    #[Test]
    public function collaboratorCannotUpdatePlaylist(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => 'Nope',
                'description' => 'Nopey Nope',
            ],
            $collaborator,
        )->assertForbidden();
    }

    #[Test]
    public function listingDeniesEditAndDeletePermissionsForCollaborator(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this
            ->getAs('api/playlists', $collaborator)
            ->assertJsonCount(1)
            ->assertJsonPath('0.permissions.edit', false)
            ->assertJsonPath('0.permissions.delete', false);
    }

    #[Test]
    public function listingSharedPlaylistOnceWithCollaboratorsOwnFolderWhenBothPutItInFolders(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $ownerFolder = PlaylistFolder::factory()->for($playlist->owner)->createOne();
        $ownerFolder->playlists()->attach($playlist);
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->createOne();
        $collaboratorFolder->playlists()->attach($playlist);

        $this
            ->getAs('api/playlists', $collaborator)
            ->assertJsonCount(1)
            ->assertJsonPath('0.folder_id', $collaboratorFolder->id);
    }
}
