<?php

namespace Tests\Feature;

use App\Http\Resources\PlaylistFolderResource;
use App\Models\Playlist;
use App\Models\PlaylistFolder;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistFolderTest extends TestCase
{
    public function testListing(): void
    {
        $user = create_user();
        PlaylistFolder::factory()->for($user)->count(3)->create();

        $this->getAs('api/playlist-folders', $user)
            ->assertJsonStructure(['*' => PlaylistFolderResource::JSON_STRUCTURE])
            ->assertJsonCount(3, '*');
    }

    public function testCreate(): void
    {
        $user = create_user();

        $this->postAs('api/playlist-folders', ['name' => 'Classical'], $user)
            ->assertJsonStructure(PlaylistFolderResource::JSON_STRUCTURE);

        $this->assertDatabaseHas(PlaylistFolder::class, ['name' => 'Classical', 'user_id' => $user->id]);
    }

    public function testUpdate(): void
    {
        $folder = PlaylistFolder::factory()->create(['name' => 'Metal']);

        $this->patchAs('api/playlist-folders/' . $folder->id, ['name' => 'Classical'], $folder->user)
            ->assertJsonStructure(PlaylistFolderResource::JSON_STRUCTURE);

        self::assertSame('Classical', $folder->fresh()->name);
    }

    public function testUnauthorizedUpdate(): void
    {
        $folder = PlaylistFolder::factory()->create(['name' => 'Metal']);

        $this->patchAs('api/playlist-folders/' . $folder->id, ['name' => 'Classical'])
            ->assertForbidden();

        self::assertSame('Metal', $folder->fresh()->name);
    }

    public function testDelete(): void
    {
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

    public function testMovingPlaylistToFolder(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($folder->user)->create();
        self::assertNull($playlist->getFolderId($folder->user));

        $this->postAs("api/playlist-folders/$folder->id/playlists", ['playlists' => [$playlist->id]], $folder->user)
            ->assertSuccessful();

        self::assertTrue($playlist->fresh()->getFolder($folder->user)->is($folder));
    }

    public function testUnauthorizedMovingPlaylistToFolderIsNotAllowed(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($folder->user)->create();
        self::assertNull($playlist->getFolderId($folder->user));

        $this->postAs("api/playlist-folders/$folder->id/playlists", ['playlists' => [$playlist->id]])
            ->assertUnprocessable();

        self::assertNull($playlist->fresh()->getFolder($folder->user));
    }

    public function testMovingPlaylistToRootLevel(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($folder->user)->create();

        $folder->playlists()->attach($playlist->id);
        self::assertTrue($playlist->refresh()->getFolder($folder->user)->is($folder));

        $this->deleteAs("api/playlist-folders/$folder->id/playlists", ['playlists' => [$playlist->id]], $folder->user)
            ->assertSuccessful();

        self::assertNull($playlist->fresh()->getFolder($folder->user));
    }

    public function testUnauthorizedMovingPlaylistToRootLevelIsNotAllowed(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($folder->user)->create();

        $folder->playlists()->attach($playlist->id);
        self::assertTrue($playlist->refresh()->getFolder($folder->user)->is($folder));

        $this->deleteAs("api/playlist-folders/$folder->id/playlists", ['playlists' => [$playlist->id]])
            ->assertUnprocessable();

        self::assertTrue($playlist->refresh()->getFolder($folder->user)->is($folder));
    }
}
