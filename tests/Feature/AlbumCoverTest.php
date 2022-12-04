<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Models\Album;
use App\Models\User;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;

class AlbumCoverTest extends TestCase
{
    private MediaMetadataService|MockInterface $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    public function testUpdate(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        /** @var User $user */
        $user = User::factory()->admin()->create();

        /** @var Album $album */
        $album = Album::factory()->create();

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'Foo', 'jpeg');

        $this->putAs('api/album/' . $album->id . '/cover', ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    public function testUpdateNotAllowedForNormalUsers(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->never();

        /** @var User $user */
        $user = User::factory()->create();

        $this->putAs('api/album/' . $album->id . '/cover', ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }
}
