<?php

namespace Tests\Feature\KoelPlus;

use App\Models\PlaylistFolder;
use App\Services\PlaylistFolderService;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistFolderTest extends PlusTestCase
{
    private PlaylistFolderService $folderService;

    public function setUp(): void
    {
        parent::setUp();

        $this->folderService = app(PlaylistFolderService::class);
    }

    #[Test]
    public function collaboratorPuttingPlaylistIntoTheirFolder(): void
    {
        $collaborator = create_user();

        $playlist = create_playlist();
        $playlist->addCollaborator($collaborator);
        $ownerFolder = PlaylistFolder::factory()->for($playlist->owner)->createOne();
        $ownerFolder->playlists()->attach($playlist);
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist)?->is($ownerFolder));
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->createOne();
        self::assertNull($this->folderService->getFolderForPlaylist($playlist, $collaborator));

        $this->postAs(
            "api/playlist-folders/{$collaboratorFolder->id}/playlists",
            ['playlists' => [$playlist->id]],
            $collaborator,
        )->assertSuccessful();

        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->fresh(), $collaborator)?->is(
            $collaboratorFolder,
        ));

        // Verify the playlist is in the owner's folder too
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->fresh())?->is($ownerFolder));
    }

    #[Test]
    public function collaboratorMovingPlaylistToRootLevel(): void
    {
        $collaborator = create_user();
        $playlist = create_playlist();
        $playlist->addCollaborator($collaborator);
        self::assertNull($this->folderService->getFolderForPlaylist($playlist));
        $ownerFolder = PlaylistFolder::factory()->for($playlist->owner)->createOne();
        $ownerFolder->playlists()->attach($playlist);
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->refresh())?->is($ownerFolder));
        $collaboratorFolder = PlaylistFolder::factory()->for($collaborator)->createOne();

        $collaboratorFolder->playlists()->attach($playlist);
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist->refresh(), $collaborator)?->is(
            $collaboratorFolder,
        ));

        $this->deleteAs(
            "api/playlist-folders/{$collaboratorFolder->id}/playlists",
            ['playlists' => [$playlist->id]],
            $collaborator,
        )->assertSuccessful();

        self::assertNull($this->folderService->getFolderForPlaylist($playlist->fresh(), $collaborator));
        // Verify the playlist is still in the owner's folder
        self::assertTrue($this->folderService->getFolderForPlaylist($playlist)?->is($ownerFolder));
    }
}
