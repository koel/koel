<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Services\ArtworkService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumCoverTest extends TestCase
{
    private ArtworkService|MockInterface $artworkService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artworkService = $this->mock(ArtworkService::class);
    }

    #[Test]
    public function update(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->artworkService
            ->expects('storeAlbumCover')
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/album/{$album->id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_admin())
            ->assertOk();
    }

    #[Test]
    public function updateNotAllowedForNormalUsers(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->artworkService->shouldNotReceive('storeAlbumCover');

        $this->putAs("api/album/{$album->id}/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_user())
            ->assertForbidden();
    }
}
