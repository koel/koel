<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Models\Song;
use App\Services\PlaylistFolderService;
use App\Values\SmartPlaylist\SmartPlaylistRule;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_playlists;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class PlaylistTest extends TestCase
{
    #[Test]
    public function listing(): void
    {
        $user = create_user();
        create_playlists(count: 3, owner: $user);

        $this
            ->getAs('api/playlists', $user)
            ->assertJsonStructure([0 => PlaylistResource::JSON_STRUCTURE])
            ->assertJsonCount(3, '*');
    }

    #[Test]
    public function creatingPlaylist(): void
    {
        /** @var PlaylistFolderService $playlistFolderService */
        $playlistFolderService = app(PlaylistFolderService::class);

        $user = create_user();

        $songs = Song::factory()->createMany(2);

        $ulid = Ulid::freeze();

        $this->postAs(
            'api/playlists',
            [
                'name' => 'Foo Bar',
                'description' => 'Foo Bar Description',
                'songs' => $songs->modelKeys(),
                'rules' => [],
                'cover' => minimal_base64_encoded_image(),
            ],
            $user,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->firstOrFail();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertSame('Foo Bar Description', $playlist->description);
        self::assertTrue($playlist->ownedBy($user));
        self::assertNull($playlistFolderService->getFolderForPlaylist($playlist));
        self::assertEqualsCanonicalizing($songs->modelKeys(), $playlist->playables->modelKeys());
        self::assertSame("$ulid.webp", $playlist->cover);
    }

    #[Test]
    public function creatingPlaylistWithNewFolderName(): void
    {
        $user = create_user();

        $this->postAs(
            'api/playlists',
            [
                'name' => 'My Playlist',
                'description' => '',
                'songs' => [],
                'rules' => [],
                'folder_name' => 'Brand New Folder',
            ],
            $user,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->firstOrFail();

        self::assertSame('My Playlist', $playlist->name);

        $folder = app(PlaylistFolderService::class)->getFolderForPlaylist($playlist);
        self::assertNotNull($folder);
        self::assertSame('Brand New Folder', $folder->name);
        self::assertTrue($folder->user->is($user));
    }

    #[Test]
    public function updatingPlaylistWithNewFolderName(): void
    {
        $playlist = create_playlist();

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => 'Updated',
                'description' => '',
                'folder_name' => 'New Folder',
            ],
            $playlist->owner,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $folder = app(PlaylistFolderService::class)->getFolderForPlaylist($playlist->refresh());
        self::assertNotNull($folder);
        self::assertSame('New Folder', $folder->name);
    }

    #[Test]
    public function creatingPlaylistWithBothFolderIdAndFolderNameFails(): void
    {
        $user = create_user();
        $folder = $user->playlistFolders()->create(['name' => 'Existing']);

        $this->postAs(
            'api/playlists',
            [
                'name' => 'My Playlist',
                'description' => '',
                'songs' => [],
                'rules' => [],
                'folder_id' => $folder->id,
                'folder_name' => 'New Folder',
            ],
            $user,
        )->assertUnprocessable();
    }

    #[Test]
    public function updatingPlaylistWithBothFolderIdAndFolderNameFails(): void
    {
        $playlist = create_playlist();
        $folder = $playlist->owner->playlistFolders()->create(['name' => 'Existing']);

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => 'Updated',
                'description' => '',
                'folder_id' => $folder->id,
                'folder_name' => 'New Folder',
            ],
            $playlist->owner,
        )->assertUnprocessable();
    }

    #[Test]
    public function createPlaylistWithoutCover(): void
    {
        /** @var PlaylistFolderService $playlistFolderService */
        $playlistFolderService = app(PlaylistFolderService::class);

        $user = create_user();

        $this->postAs(
            'api/playlists',
            [
                'name' => 'Foo Bar',
                'description' => 'Foo Bar Description',
                'songs' => [],
                'rules' => [],
            ],
            $user,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->firstOrFail();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertSame('Foo Bar Description', $playlist->description);
        self::assertTrue($playlist->ownedBy($user));
        self::assertNull($playlistFolderService->getFolderForPlaylist($playlist));
        self::assertEmpty($playlist->cover);
    }

    #[Test]
    public function createPlaylistWithoutDescription(): void
    {
        $user = create_user();

        $this->postAs(
            'api/playlists',
            [
                'name' => 'Foo Bar',
                'description' => '',
                'songs' => [],
                'rules' => [],
            ],
            $user,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Foo Bar', $playlist->name);
        self::assertSame('', $playlist->description);
    }

    #[Test]
    public function creatingSmartPlaylist(): void
    {
        /** @var PlaylistFolderService $playlistFolderService */
        $playlistFolderService = app(PlaylistFolderService::class);

        $user = create_user();

        $rule = SmartPlaylistRule::make([
            'model' => 'artist.name',
            'operator' => 'is',
            'value' => ['Bob Dylan'],
        ]);

        $this->postAs(
            'api/playlists',
            [
                'name' => 'Smart Foo Bar',
                'description' => 'Smart Foo Bar Description',
                'rules' => [
                    [
                        'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                        'rules' => [$rule->toArray()],
                    ],
                ],
                'cover' => minimal_base64_encoded_image(),
            ],
            $user,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        $playlist = Playlist::query()->latest()->first();

        self::assertSame('Smart Foo Bar', $playlist->name);
        self::assertSame('Smart Foo Bar Description', $playlist->description);
        self::assertTrue($playlist->ownedBy($user));
        self::assertTrue($playlist->is_smart);
        self::assertCount(1, $playlist->rule_groups);
        self::assertNull($playlistFolderService->getFolderForPlaylist($playlist));
        self::assertTrue($rule->equals($playlist->rule_groups[0]->rules[0]));
        self::assertNotNull($playlist->cover);
    }

    #[Test]
    public function creatingSmartPlaylistFailsIfSongsProvided(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Smart Foo Bar',
            'description' => 'Smart Foo Bar Description',
            'rules' => [
                [
                    'id' => '2a4548cd-c67f-44d4-8fec-34ff75c8a026',
                    'rules' => [
                        SmartPlaylistRule::make([
                            'model' => 'artist.name',
                            'operator' => 'is',
                            'value' => ['Bob Dylan'],
                        ])->toArray(),
                    ],
                ],
            ],
            'songs' => Song::factory()->createMany(2)->modelKeys(),
        ])->assertUnprocessable();
    }

    #[Test]
    public function creatingPlaylistWithNonExistentSongsFails(): void
    {
        $this->postAs('api/playlists', [
            'name' => 'Foo Bar',
            'description' => 'Foo Bar Description',
            'rules' => [],
            'songs' => ['foo'],
        ])->assertUnprocessable();
    }

    #[Test]
    public function updateKeepingCoverIntact(): void
    {
        $playlist = create_playlist([
            'cover' => 'neat-cover.webp',
        ]);

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => 'Bar',
                'description' => 'Bar Description',
            ],
            $playlist->owner,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertSame('Bar', $playlist->refresh()->name);
        self::assertSame('Bar Description', $playlist->description);
        self::assertSame('neat-cover.webp', $playlist->cover);
    }

    #[Test]
    public function updateReplacingCover(): void
    {
        $playlist = create_playlist([
            'cover' => 'neat-cover.webp',
        ]);

        $ulid = Ulid::freeze();

        $this
            ->putAs(
                "api/playlists/{$playlist->id}",
                [
                    'name' => 'Bar',
                    'description' => 'Bar Description',
                    'cover' => minimal_base64_encoded_image(),
                ],
                $playlist->owner,
            )
            ->assertSuccessful()
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertSame("$ulid.webp", $playlist->refresh()->cover);
    }

    #[Test]
    public function updateRemovingCover(): void
    {
        $playlist = create_playlist([
            'cover' => 'neat-cover.webp',
        ]);

        $this
            ->putAs(
                "api/playlists/{$playlist->id}",
                [
                    'name' => 'Bar',
                    'description' => 'Bar Description',
                    'cover' => '',
                ],
                $playlist->owner,
            )
            ->assertSuccessful()
            ->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertEmpty($playlist->refresh()->cover);
    }

    #[Test]
    public function updateWithoutDescription(): void
    {
        $playlist = create_playlist(['name' => 'Foo', 'description' => 'Bar Description']);

        $this->putAs(
            "api/playlists/{$playlist->id}",
            [
                'name' => 'Bar',
                'description' => '',
            ],
            $playlist->owner,
        )->assertJsonStructure(PlaylistResource::JSON_STRUCTURE);

        self::assertSame('Bar', $playlist->refresh()->name);
        self::assertSame('', $playlist->description);
    }

    #[Test]
    public function nonOwnerCannotUpdatePlaylist(): void
    {
        $playlist = create_playlist(['name' => 'Foo']);

        $this->putAs("api/playlists/{$playlist->id}", [
            'name' => 'Qux',
            'description' => 'Qux Description',
        ])->assertForbidden();

        self::assertSame('Foo', $playlist->refresh()->name);
    }

    #[Test]
    public function deletePlaylist(): void
    {
        $playlist = create_playlist();

        $this->deleteAs("api/playlists/{$playlist->id}", [], $playlist->owner);

        $this->assertModelMissing($playlist);
    }

    #[Test]
    public function nonOwnerCannotDeletePlaylist(): void
    {
        $playlist = create_playlist();

        $this->deleteAs("api/playlists/{$playlist->id}")->assertForbidden();

        $this->assertModelExists($playlist);
    }
}
