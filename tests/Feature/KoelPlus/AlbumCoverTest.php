<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Services\ArtworkService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class AlbumCoverTest extends PlusTestCase
{
    private ArtworkService|MockInterface $artworkService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artworkService = $this->mock(ArtworkService::class);
    }

    #[Test]
    public function albumOwnerCanUploadOrDeleteCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->for($user)->create();

        $this->artworkService
            ->expects('storeAlbumCover')
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), minimal_base64_encoded_image());

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => minimal_base64_encoded_image()], $user)
            ->assertOk();

        $this->deleteAs("api/albums/{$album->id}/cover", [], $user)
            ->assertNoContent();
    }

    #[Test]
    public function nonOwnerCannotUploadOrDeleteCover(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();

        self::assertFalse($album->belongsToUser($user));

        $this->artworkService->shouldNotReceive('storeAlbumCover');

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => minimal_base64_encoded_image()], $user)
            ->assertForbidden();

        $this->deleteAs("api/albums/{$album->id}/cover", [], $user)->assertForbidden();
    }

    #[Test]
    public function evenAdminsCannotUploadCoverIfNotOwning(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();
        $admin = create_admin();

        $this->putAs("api/albums/{$album->id}/cover", ['cover' => minimal_base64_encoded_image()], $admin)
            ->assertForbidden();

        $this->deleteAs("api/albums/{$album->id}/cover", [], $admin)->assertForbidden();
    }
}
