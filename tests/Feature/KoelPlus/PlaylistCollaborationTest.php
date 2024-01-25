<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\PlaylistCollaborationTokenResource;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\PlaylistCollaborationToken;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistCollaborationTest extends PlusTestCase
{
    public function testCreatePlaylistCollaborationToken(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->postAs("api/playlists/$playlist->id/collaborators/invite", [], $playlist->user)
            ->assertJsonStructure(PlaylistCollaborationTokenResource::JSON_STRUCTURE);
    }

    public function testAcceptPlaylistCollaborationViaToken(): void
    {
        /** @var PlaylistCollaborationToken $token */
        $token = PlaylistCollaborationToken::factory()->create();
        $user = create_user();

        $this->postAs('api/playlists/collaborators/accept', ['token' => $token->token], $user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertTrue($token->playlist->hasCollaborator($user));
    }
}
