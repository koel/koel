<?php

namespace Tests\Feature;

use App\Http\Resources\PlaylistFolderResource;
use App\Models\PlaylistFolder;
use App\Services\Playlist\PlaylistFolderService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistFolderTest extends TestCase
{
    private PlaylistFolderService $folderService;

    public function setUp(): void
    {
        parent::setUp();

        $this->folderService = app(PlaylistFolderService::class);
    }

    #[Test]
    public function listing(): void
    {
        $user = create_user();
        PlaylistFolder::factory()
            ->for($user)
            ->count(2)
            ->create();

        $this
            ->getAs('api/playlist-folders', $user)
            ->assertJsonStructure([0 => PlaylistFolderResource::JSON_STRUCTURE])
            ->assertJsonCount(2);
    }

    #[Test]
    public function create(): void
    {
        $user = create_user();

        $this->postAs(
            'api/playlist-folders',
            ['name' => 'Classical'],
            $user,
        )->assertJsonStructure(PlaylistFolderResource::JSON_STRUCTURE);

        $this->assertDatabaseHas(PlaylistFolder::class, ['name' => 'Classical', 'user_id' => $user->id]);
    }

    #[Test]
    public function update(): void
    {
        $folder = PlaylistFolder::factory()->createOne(['name' => 'Metal']);

        $this->patchAs(
            "api/playlist-folders/{$folder->id}",
            ['name' => 'Classical'],
            $folder->user,
        )->assertJsonStructure(PlaylistFolderResource::JSON_STRUCTURE);

        self::assertSame('Classical', $folder->fresh()->name);
    }

    #[Test]
    public function unauthorizedUpdate(): void
    {
        $folder = PlaylistFolder::factory()->createOne(['name' => 'Metal']);

        $this->patchAs("api/playlist-folders/{$folder->id}", ['name' => 'Classical'])->assertForbidden();

        self::assertSame('Metal', $folder->fresh()->name);
    }

    #[Test]
    public function destroy(): void
    {
        $folder = PlaylistFolder::factory()->createOne();

        $this->deleteAs(
            "api/playlist-folders/{$folder->id}",
            ['name' => 'Classical'],
            $folder->user,
        )->assertNoContent();

        $this->assertModelMissing($folder);
    }

    #[Test]
    public function nonAuthorizedDelete(): void
    {
        $folder = PlaylistFolder::factory()->createOne();

        $this->deleteAs("api/playlist-folders/{$folder->id}", ['name' => 'Classical'])->assertForbidden();

        $this->assertModelExists($folder);
    }

    #[Test]
    public function movePlaylistToFolder(): void
    {
        $playlist = create_playlist();
        $folder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        self::assertNull($this->folderService->getFolderForPlaylist($playlist));

        $this->postAs(
            "api/playlist-folders/{$folder->id}/playlists",
            ['playlists' => [$playlist->id]],
            $folder->user,
        )->assertSuccessful();

        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->fresh())?->is($folder));
    }

    #[Test]
    public function unauthorizedMovingPlaylistToFolderIsNotAllowed(): void
    {
        $playlist = create_playlist();
        $folder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        self::assertNull($this->folderService->getFolderForPlaylist($playlist));

        $this->postAs("api/playlist-folders/{$folder->id}/playlists", ['playlists' => [
            $playlist->id,
        ]])->assertUnprocessable();

        self::assertNull($this->folderService->getFolderForPlaylist($playlist->fresh()));
    }

    #[Test]
    public function movePlaylistToRootLevel(): void
    {
        $playlist = create_playlist();
        $folder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        $folder->playlists()->attach($playlist);
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->refresh())?->is($folder));

        $this->deleteAs(
            "api/playlist-folders/{$folder->id}/playlists",
            ['playlists' => [$playlist->id]],
            $folder->user,
        )->assertSuccessful();

        self::assertNull($this->folderService->getFolderForPlaylist($playlist->fresh()));
    }

    #[Test]
    public function unauthorizedMovingPlaylistToRootLevelIsNotAllowed(): void
    {
        $playlist = create_playlist();
        $folder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        $folder->playlists()->attach($playlist);
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->refresh())?->is($folder));

        $this->deleteAs("api/playlist-folders/{$folder->id}/playlists", ['playlists' => [
            $playlist->id,
        ]])->assertUnprocessable();

        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->refresh())->is($folder));
    }
}
