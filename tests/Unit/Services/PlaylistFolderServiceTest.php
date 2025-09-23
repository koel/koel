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
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create(['name' => 'Metal']);

        $this->service->renameFolder($folder, 'Classical');

        self::assertSame('Classical', $folder->fresh()->name);
    }

    #[Test]
    public function addPlaylistsToFolder(): void
    {
        $user = create_user();
        $playlists = create_playlists(count: 3, owner: $user);

        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->for($user)->create();

        $this->service->addPlaylistsToFolder($folder, $playlists->modelKeys());

        self::assertCount(3, $folder->playlists);
    }

    #[Test]
    public function aPlaylistCannotBelongToMultipleFoldersByOneUser(): void
    {
        $playlist = create_playlist();

        /** @var PlaylistFolder $existingFolder */
        $existingFolder = PlaylistFolder::factory()->for($playlist->owner)->create();

        $existingFolder->playlists()->attach($playlist);

        /** @var PlaylistFolder $newFolder */
        $newFolder = PlaylistFolder::factory()->for($playlist->owner)->create();

        $this->service->addPlaylistsToFolder($newFolder, [$playlist->id]);

        self::assertSame(1, $playlist->refresh()->folders->count());
    }

    #[Test]
    public function aPlaylistCanBelongToMultipleFoldersFromDifferentUsers(): void
    {
        /** @var PlaylistFolder $existingFolderFromAnotherUser */
        $existingFolderFromAnotherUser = PlaylistFolder::factory()->create();

        $playlist = create_playlist();
        $existingFolderFromAnotherUser->playlists()->attach($playlist);

        /** @var PlaylistFolder $newFolder */
        $newFolder = PlaylistFolder::factory()->for($playlist->owner)->create();

        $this->service->addPlaylistsToFolder($newFolder, [$playlist->id]);

        self::assertSame(2, $playlist->refresh()->folders->count());
    }

    #[Test]
    public function movePlaylistsToRootLevel(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        $playlists = create_playlists(count: 3);
        $folder->playlists()->attach($playlists);

        $this->service->movePlaylistsToRootLevel($folder, $playlists->modelKeys());

        self::assertCount(0, $folder->playlists);

        $playlists->each(static fn (Playlist $playlist) => self::assertNull($playlist->refresh()->getFolder())); // @phpstan-ignore-line
    }
}
