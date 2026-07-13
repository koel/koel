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
        PlaylistFolder::factory()->for($user)->count(2)->create();

        $this
            ->getAs('api/playlist-folders', $user)
            ->assertJsonStructure([0 => PlaylistFolderResource::JSON_STRUCTURE])
            ->assertJsonCount(2);
    }

    #[Test]
    public function create(): void
    {
        $user = create_user();

        $this
            ->postAs('api/playlist-folders', ['name' => 'Classical'], $user)
            ->assertJsonStructure(PlaylistFolderResource::JSON_STRUCTURE)
            ->assertJsonPath('parent_id', null);

        $this->assertDatabaseHas(PlaylistFolder::class, [
            'name' => 'Classical',
            'parent_id' => null,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function creatingUnderAMissingParentIsNotAllowed(): void
    {
        $user = create_user();

        $this
            ->postAs('api/playlist-folders', ['name' => 'Classical', 'parent_id' => fake()->uuid()], $user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseMissing(PlaylistFolder::class, [
            'name' => 'Classical',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function createNestedFolder(): void
    {
        $user = create_user();
        $parent = PlaylistFolder::factory()->for($user)->createOne();

        $response = $this->postAs('api/playlist-folders', ['name' => 'Live Sets', 'parent_id' => $parent->id], $user);

        $response->assertSuccessful()->assertJsonPath('name', 'Live Sets')->assertJsonPath('parent_id', $parent->id);

        $this->assertDatabaseHas(PlaylistFolder::class, [
            'name' => 'Live Sets',
            'parent_id' => $parent->id,
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function creatingUnderAnotherUsersFolderIsNotAllowed(): void
    {
        $user = create_user();
        $parent = PlaylistFolder::factory()->createOne();

        $this
            ->postAs('api/playlist-folders', ['name' => 'Live Sets', 'parent_id' => $parent->id], $user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        $this->assertDatabaseMissing(PlaylistFolder::class, [
            'name' => 'Live Sets',
            'user_id' => $user->id,
        ]);
    }

    #[Test]
    public function update(): void
    {
        $parent = PlaylistFolder::factory()->createOne();
        $folder = PlaylistFolder::factory()->for($parent->user)->for($parent, 'parent')->createOne(['name' => 'Metal']);

        $this
            ->patchAs("api/playlist-folders/{$folder->id}", ['name' => 'Classical'], $folder->user)
            ->assertJsonStructure(PlaylistFolderResource::JSON_STRUCTURE)
            ->assertJsonPath('parent_id', $parent->id);

        $updatedFolder = $folder->fresh();
        self::assertSame('Classical', $updatedFolder->name);
        self::assertTrue($updatedFolder->parent->is($parent));
    }

    #[Test]
    public function moveBetweenParents(): void
    {
        $user = create_user();
        $parent = PlaylistFolder::factory()->for($user)->createOne();
        $newParent = PlaylistFolder::factory()->for($user)->createOne();
        $folder = PlaylistFolder::factory()->for($user)->for($parent, 'parent')->createOne();

        $this
            ->patchAs("api/playlist-folders/{$folder->id}", ['parent_id' => $newParent->id], $user)
            ->assertSuccessful()
            ->assertJsonPath('parent_id', $newParent->id);

        self::assertTrue($folder->fresh()->parent->is($newParent));
    }

    #[Test]
    public function moveToRoot(): void
    {
        $parent = PlaylistFolder::factory()->createOne();
        $folder = PlaylistFolder::factory()->for($parent->user)->for($parent, 'parent')->createOne();

        $this
            ->patchAs("api/playlist-folders/{$folder->id}", ['parent_id' => null], $folder->user)
            ->assertSuccessful()
            ->assertJsonPath('parent_id', null);

        self::assertNull($folder->fresh()->parent_id);
    }

    #[Test]
    public function movingUnderAnotherUsersFolderIsNotAllowed(): void
    {
        $folder = PlaylistFolder::factory()->createOne();
        $parent = PlaylistFolder::factory()->createOne();

        $this
            ->patchAs("api/playlist-folders/{$folder->id}", ['parent_id' => $parent->id], $folder->user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        self::assertNull($folder->fresh()->parent_id);
    }

    #[Test]
    public function movingUnderItselfIsNotAllowed(): void
    {
        $folder = PlaylistFolder::factory()->createOne();

        $this
            ->patchAs("api/playlist-folders/{$folder->id}", ['parent_id' => $folder->id], $folder->user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        self::assertNull($folder->fresh()->parent_id);
    }

    #[Test]
    public function movingUnderADescendantIsNotAllowed(): void
    {
        $folder = PlaylistFolder::factory()->createOne();
        $child = PlaylistFolder::factory()->for($folder->user)->for($folder, 'parent')->createOne();
        $grandchild = PlaylistFolder::factory()->for($folder->user)->for($child, 'parent')->createOne();

        $this
            ->patchAs("api/playlist-folders/{$folder->id}", ['parent_id' => $grandchild->id], $folder->user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_id');

        self::assertNull($folder->fresh()->parent_id);
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
        $child = PlaylistFolder::factory()->for($folder->user)->for($folder, 'parent')->createOne();
        $grandchild = PlaylistFolder::factory()->for($folder->user)->for($child, 'parent')->createOne();
        $directPlaylist = create_playlist();
        $directPlaylist->users()->detach();
        $directPlaylist->users()->attach($folder->user, ['role' => 'owner']);
        $folder->playlists()->attach($directPlaylist);
        $childPlaylist = create_playlist();
        $childPlaylist->users()->detach();
        $childPlaylist->users()->attach($folder->user, ['role' => 'owner']);
        $child->playlists()->attach($childPlaylist);

        $this->deleteAs(
            "api/playlist-folders/{$folder->id}",
            ['name' => 'Classical'],
            $folder->user,
        )->assertNoContent();

        $this->assertModelMissing($folder);
        $this->assertModelExists($directPlaylist);
        $this->assertModelExists($childPlaylist);
        self::assertNull($this->folderService->getFolderForPlaylist($directPlaylist->fresh()));
        self::assertTrue($this->folderService->getFolderForPlaylist($childPlaylist->fresh())?->is($child));
        self::assertNull($child->fresh()->parent_id);
        self::assertTrue($grandchild->fresh()->parent->is($child));
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
