<?php

namespace Tests\Feature;

use App\Models\PlaylistFolder;
use App\Models\User;
use Tests\TestCase;

class PlaylistFolderTest extends TestCase
{
    private const JSON_STRUCTURE = [
        'type',
        'id',
        'name',
        'user_id',
        'created_at',
    ];

    public function testListing(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        PlaylistFolder::factory()->for($user)->count(3)->create();

        $this->getAs('api/playlist-folders', $user)
            ->assertJsonStructure(['*' => self::JSON_STRUCTURE])
            ->assertJsonCount(3, '*');
    }

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
}
