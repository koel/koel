<?php

namespace Tests\Unit\Services;

use App\Models\Playlist;
use App\Models\PlaylistFolder;
use App\Services\PlaylistFolderService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_playlists;
use function Tests\create_user;

class PlaylistFolderServiceTest extends TestCase
{
    private PlaylistFolderService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = new PlaylistFolderService();
    }

    #[Test]
    public function create(): void
    {
        $user = create_user();

        self::assertCount(0, $user->playlistFolders);

        $this->service->createFolder($user, 'Classical');

        self::assertCount(1, $user->refresh()->playlistFolders);
        self::assertSame('Classical', $user->playlistFolders[0]->name);
    }

    #[Test]
    public function update(): void
    {
        $folder = PlaylistFolder::factory()->createOne(['name' => 'Metal']);

        $this->service->renameFolder($folder, 'Classical');

        self::assertSame('Classical', $folder->fresh()->name);
    }

    #[Test]
    public function addPlaylistsToFolder(): void
    {
        $user = create_user();
        $playlists = create_playlists(count: 3, owner: $user);
        $folder = PlaylistFolder::factory()->for($user)->createOne();

        $this->service->addPlaylistsToFolder($folder, $playlists->modelKeys());

        self::assertCount(3, $folder->playlists);
    }

    #[Test]
    public function aPlaylistCannotBelongToMultipleFoldersByOneUser(): void
    {
        $playlist = create_playlist();
        $existingFolder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        $existingFolder->playlists()->attach($playlist);
        $newFolder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        $this->service->addPlaylistsToFolder($newFolder, [$playlist->id]);

        self::assertSame(1, $playlist->refresh()->folders->count());
    }

    #[Test]
    public function aPlaylistCanBelongToMultipleFoldersFromDifferentUsers(): void
    {
        $existingFolderFromAnotherUser = PlaylistFolder::factory()->createOne();

        $playlist = create_playlist();
        $existingFolderFromAnotherUser->playlists()->attach($playlist);
        $newFolder = PlaylistFolder::factory()->for($playlist->owner)->createOne();

        $this->service->addPlaylistsToFolder($newFolder, [$playlist->id]);

        self::assertSame(2, $playlist->refresh()->folders->count());
    }

    #[Test]
    public function movePlaylistsToRootLevel(): void
    {
        $folder = PlaylistFolder::factory()->createOne();

        $playlists = create_playlists(count: 3);
        $folder->playlists()->attach($playlists);

        $this->service->movePlaylistsToRootLevel($folder, $playlists->modelKeys());

        self::assertCount(0, $folder->playlists);

        $playlists->each(fn (Playlist $playlist) => self::assertNull( // @phpstan-ignore-line
            $this->service->getFolderForPlaylist($playlist->refresh()),
        ));
    }
}
