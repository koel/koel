<?php

namespace Tests\Unit\Listeners;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Listeners\MakePlaylistSongsPublic;
use App\Models\PlaylistCollaborationToken;
use App\Services\PlaylistService;
use Mockery;
use Tests\TestCase;

use function Tests\create_user;

class MakePlaylistSongsPublicTest extends TestCase
{
    public function testHandle(): void
    {
        $collaborator = create_user();

        /** @var PlaylistCollaborationToken $token */
        $token = PlaylistCollaborationToken::factory()->create();

        $service = Mockery::mock(PlaylistService::class);

        $service->shouldReceive('makePlaylistSongsPublic')
            ->with($token->playlist)
            ->once();

        (new MakePlaylistSongsPublic($service))->handle(new NewPlaylistCollaboratorJoined($collaborator, $token));
    }
}
