<?php

namespace Tests\Unit\Services;

use App\Models\Playlist;
use App\Models\PlaylistFolder;
use App\Services\PlaylistFolderService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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

        self::assertCount(0, $user->playlist_folders);

        $this->service->createFolder($user, 'Classical');

        self::assertCount(1, $user->refresh()->playlist_folders);
        self::assertSame('Classical', $user->playlist_folders[0]->name);
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

        /** @var Collection<array-key, Playlist> $playlists */
        $playlists = Playlist::factory()->for($user)->count(3)->create();

        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->for($user)->create();

        $this->service->addPlaylistsToFolder($folder, $playlists->modelKeys());

        self::assertCount(3, $folder->playlists);
    }

    #[Test]
    public function aPlaylistCannotBelongToMultipleFoldersByOneUser(): void
    {
        $user = create_user();

        /** @var PlaylistFolder $existingFolder */
        $existingFolder = PlaylistFolder::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();
        $existingFolder->playlists()->attach($playlist);

        /** @var PlaylistFolder $newFolder */
        $newFolder = PlaylistFolder::factory()->for($user)->create();

        $this->service->addPlaylistsToFolder($newFolder, [$playlist->id]);

        self::assertSame(1, $playlist->refresh()->folders->count());
    }

    #[Test]
    public function aPlaylistCanBelongToMultipleFoldersFromDifferentUsers(): void
    {
        $user = create_user();

        /** @var PlaylistFolder $existingFolderFromAnotherUser */
        $existingFolderFromAnotherUser = PlaylistFolder::factory()->create();

        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->for($user)->create();
        $existingFolderFromAnotherUser->playlists()->attach($playlist);

        /** @var PlaylistFolder $newFolder */
        $newFolder = PlaylistFolder::factory()->for($user)->create();

        $this->service->addPlaylistsToFolder($newFolder, [$playlist->id]);

        self::assertSame(2, $playlist->refresh()->folders->count());
    }

    #[Test]
    public function movePlaylistsToRootLevel(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        /** @var Collection<array-key, Playlist> $playlists */
        $playlists = Playlist::factory()->count(3)->create();
        $folder->playlists()->attach($playlists);

        $this->service->movePlaylistsToRootLevel($folder, $playlists->modelKeys());

        self::assertCount(0, $folder->playlists);

        $playlists->each(static fn (Playlist $playlist) => self::assertNull($playlist->refresh()->getFolder()));
    }
}
