<?php

namespace Tests\Unit\Services;

use App\Models\Playlist;
use App\Models\PlaylistFolder;
use App\Services\PlaylistFolderService;
use Illuminate\Support\Collection;
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

    public function testCreate(): void
    {
        $user = create_user();

        self::assertCount(0, $user->playlist_folders);

        $this->service->createFolder($user, 'Classical');

        self::assertCount(1, $user->refresh()->playlist_folders);
        self::assertSame('Classical', $user->playlist_folders[0]->name);
    }

    public function testUpdate(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create(['name' => 'Metal']);

        $this->service->renameFolder($folder, 'Classical');

        self::assertSame('Classical', $folder->fresh()->name);
    }

    public function testAddPlaylistsToFolder(): void
    {
        $user = create_user();

        /** @var Collection|array<array-key, Playlist> $playlists */
        $playlists = Playlist::factory()->for($user)->count(3)->create();

        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->for($user)->create();

        $this->service->addPlaylistsToFolder($folder, $playlists->pluck('id')->all());

        self::assertCount(3, $folder->playlists);
    }

    public function testMovePlaylistsToRootLevel(): void
    {
        /** @var PlaylistFolder $folder */
        $folder = PlaylistFolder::factory()->create();

        /** @var Collection|array<array-key, Playlist> $playlists */
        $playlists = Playlist::factory()->count(3)->create();
        $folder->playlists()->attach($playlists->pluck('id')->all());

        $this->service->movePlaylistsToRootLevel($folder, $playlists->pluck('id')->all());

        self::assertCount(0, $folder->playlists);

        $playlists->each(static fn (Playlist $playlist) => self::assertNull($playlist->refresh()->getFolder()));
    }
}
