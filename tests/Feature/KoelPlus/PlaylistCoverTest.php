<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Playlist;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use Tests\PlusTestCase;

use function Tests\create_user;

class PlaylistCoverTest extends PlusTestCase
{
    private MockInterface|MediaMetadataService $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    public function testCollaboratorCanUploadCover(): void
    {
        /** @var Playlist $playlist */
        $playlist = Playlist::factory()->create();
        $collaborator = create_user();
        $playlist->addCollaborator($collaborator);

        $this->mediaMetadataService
            ->shouldReceive('writePlaylistCover')
            ->once()
            ->with(Mockery::on(static fn (Playlist $target) => $target->is($playlist)), 'Foo', 'jpeg');

        $this->putAs(
            "api/playlists/$playlist->id/cover",
            ['cover' => 'data:image/jpeg;base64,Rm9v'],
            $collaborator
        )
            ->assertOk();
    }
}
