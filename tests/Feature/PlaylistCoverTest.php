<?php

namespace Tests\Feature;

use App\Models\Playlist;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

use function Tests\create_user;

class PlaylistCoverTest extends TestCase
{
    private MockInterface|MediaMetadataService $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    public function testUploadCover(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        self::assertNull($playlist->cover);

        $this->mediaMetadataService
            ->shouldReceive('writePlaylistCover')
            ->once()
            ->with(Mockery::on(static fn (Playlist $target) => $target->is($playlist)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/playlists/$playlist->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $playlist->user)
            ->assertOk();
    }

    public function testUploadCoverNotAllowedForNonOwner(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();

        $this->mediaMetadataService->shouldNotReceive('writePlaylistCover');

        $this->putAs("api/playlists/$playlist->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_user())
            ->assertForbidden();
    }
}
