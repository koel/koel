<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

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
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/album/$album->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_admin())
            ->assertOk();
    }

    public function testUpdateNotAllowedForNormalUsers(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->mediaMetadataService->shouldNotReceive('writeAlbumCover');

        $this->putAs('api/album/' . $album->id . '/cover', ['cover' => 'data:image/jpeg;base64,Rm9v'], create_user())
            ->assertForbidden();
    }
}
