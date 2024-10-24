<?php

namespace Tests\Unit\Listeners;

use App\Events\NewPlaylistCollaboratorJoined;
use App\Listeners\MakePlaylistSongsPublic;
use App\Models\PlaylistCollaborationToken;
use App\Services\PlaylistService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class MakePlaylistSongsPublicTest extends TestCase
{
    #[Test]
    public function handle(): void
    {
        $collaborator = create_user();

        /** @var PlaylistCollaborationToken $token */
        $token = PlaylistCollaborationToken::factory()->create();

        $service = Mockery::mock(PlaylistService::class);

        $service->shouldReceive('makePlaylistContentPublic')
            ->with($token->playlist)
            ->once();

        (new MakePlaylistSongsPublic($service))->handle(new NewPlaylistCollaboratorJoined($collaborator, $token));
    }
}
