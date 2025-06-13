<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumCoverTest extends PlusTestCase
{
    private MediaMetadataService|MockInterface $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = $this->mock(MediaMetadataService::class);
    }

    #[Test]
    public function albumOwnerCanUploadCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->for($user)->create();

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/albums/{$album->public_id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    #[Test]
    public function nonOwnerCannotUploadCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();

        self::assertFalse($album->belongsToUser($user));

        $this->mediaMetadataService->shouldNotReceive('writeAlbumCover');

        $this->putAs("api/albums/{$album->public_id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }

    #[Test]
    public function evenAdminsCannotUploadCoverIfNotOwning(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->putAs("api/albums/{$album->public_id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_admin())
            ->assertForbidden();
    }
}
