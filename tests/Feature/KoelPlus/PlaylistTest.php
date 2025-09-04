<?php

namespace Tests\Feature\KoelPlus;

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

        $this->putAs("api/playlists/{$playlist->id}", [
            'name' => 'Nope',
            'description' => 'Nopey Nope',
        ], $collaborator)
            ->assertForbidden();
    }
}
