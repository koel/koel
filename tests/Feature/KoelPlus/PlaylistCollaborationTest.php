<?php

namespace Tests\Feature\KoelPlus;

use App\Http\Resources\PlaylistCollaborationTokenResource;
use App\Http\Resources\PlaylistResource;
use App\Models\PlaylistCollaborationToken;
use App\Models\PlaylistFolder;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistCollaborationTest extends PlusTestCase
{
    #[Test]
    public function createPlaylistCollaborationToken(): void
    {
        $playlist = create_playlist();

        $this->postAs("api/playlists/{$playlist->id}/collaborators/invite", [], $playlist->owner)
            ->assertJsonStructure(PlaylistCollaborationTokenResource::JSON_STRUCTURE);
    }

    #[Test]
    public function acceptPlaylistCollaborationViaToken(): void
    {
        /** @var PlaylistCollaborationToken $token */
        $token = PlaylistCollaborationToken::factory()->create();
        $user = create_user();

        $this->postAs('api/playlists/collaborators/accept', ['token' => $token->token], $user)
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertTrue($token->playlist->hasCollaborator($user));
    }

    #[Test]
    public function removeCollaborator(): void
    {
        $playlist = create_playlist();
        $user = create_user();
        $playlist->addCollaborator($user);

        self::assertTrue($playlist->refresh()->hasCollaborator($user));

        $this->deleteAs(
            "api/playlists/{$playlist->id}/collaborators",
            ['collaborator' => $user->public_id],
            $playlist->owner,
        );

        self::assertFalse($playlist->refresh()->hasCollaborator($user));
    }

    #[Test]
    public function collaboratorsCanAccessSharedPlaylistAtRootLevel(): void
    {
        $playlist = create_playlist();
        $collaborator = create_user();

        $playlist->addCollaborator($collaborator);

        $this->getAs('/api/data', $collaborator)->assertJsonPath('playlists.0.id', $playlist->id);
    }

    #[Test]
    public function collaboratorsCanAccessSharedPlaylistInFolder(): void
    {
        $playlist = create_playlist();
        $playlist->folders()->attach(PlaylistFolder::factory()->create());
        $collaborator = create_user();

        $playlist->addCollaborator($collaborator);

        $this->getAs('/api/data', $collaborator)->assertJsonPath('playlists.0.id', $playlist->id);
    }
}
