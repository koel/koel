<?php

namespace Tests\Feature\V6;

use App\Models\PlaylistFolder;
use App\Models\Song;
use App\Models\User;

use function Functional\pluck;

class PlaylistFolderTest extends TestCase
{
    private const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'user_id',
        'created_at',
    ];

    public function testCreate(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->postAs('api/playlist-folders', ['name' => 'Classical'], $user)
            ->assertJsonStructure(self::JSON_STRUCTURE);

        $this->assertDatabaseHas(PlaylistFolder::class, ['name' => 'Classical', 'user_id' => $user->id]);
    }

    public function testUpdate(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create(['name' => 'Metal']);

        $this->patchAs('api/playlist-folders/' . $folder->id, ['name' => 'Classical'], $folder->user)
            ->assertJsonStructure(self::JSON_STRUCTURE);

        self::assertSame('Classical', $folder->fresh()->name);
    }

    public function testUnauthorizedUpdate(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create(['name' => 'Metal']);

        $this->patchAs('api/playlist-folders/' . $folder->id, ['name' => 'Classical'])
            ->assertForbidden();

        self::assertSame('Metal', $folder->fresh()->name);
    }

    public function testDelete(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        $this->deleteAs('api/playlist-folders/' . $folder->id, ['name' => 'Classical'], $folder->user)
            ->assertNoContent();

        self::assertModelMissing($folder);
    }

    public function testNonAuthorizedDelete(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        $this->deleteAs('api/playlist-folders/' . $folder->id, ['name' => 'Classical'])
            ->assertForbidden();

        self::assertModelExists($folder);
    }

    public function testCreatePlaylistForFolder()
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        $this->postAs('api/playlists', [
            "name" => "NewForFolder",
            "songs" => [],
            "folder_id" => $folder->id
        ], $folder->user)
            ->assertJsonStructure([
                'created_at',
                'folder_id',
                'id',
                'is_smart',
                'name',
                'rules',
                'type',
                'user_id',
            ]);
    }

    public function testCreatePlaylistForFolderWithSongs()
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        $playlist = $this->postAs('api/playlists', [
            "name" => "NewForFolder",
            "songs" => Song::factory(3)->create()->pluck('id'),
            "folder_id" => $folder->id
        ], $folder->user)->decodeResponseJson();

        $playlistId = json_decode($playlist->json, true)['id'];

        $this->getAs('api/playlists/' . $playlistId . '/songs')
        ->assertJsonCount(3);
    }
}
