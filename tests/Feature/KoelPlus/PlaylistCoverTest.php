<?php

namespace Tests\Feature\KoelPlus;

use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class PlaylistCoverTest extends PlusTestCase
{
    #[Test]
    public function collaboratorCanNotUploadCover(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->putAs(
            "api/playlists/{$playlist->id}/cover",
            ['cover' => minimal_base64_encoded_image()],
            $collaborator
        )
            ->assertForbidden();
    }

    #[Test]
    public function collaboratorCannotDeleteCover(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->deleteAs("api/playlists/{$playlist->id}/cover", [], $collaborator)
            ->assertForbidden();
    }
}
