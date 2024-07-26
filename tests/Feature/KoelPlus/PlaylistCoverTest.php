<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Playlist;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistCoverTest extends PlusTestCase
{
    public function testCollaboratorCanNotUploadCover(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->putAs("api/playlists/$playlist->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $collaborator)
            ->assertForbidden();
    }

    public function testCollaboratorCannotDeleteCover(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->deleteAs("api/playlists/$playlist->id/cover", [], $collaborator)
            ->assertForbidden();
    }
}
