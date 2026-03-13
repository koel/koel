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
        $token = PlaylistCollaborationToken::factory()->createOne();

        $service = Mockery::mock(PlaylistService::class);
        $service->expects('makePlaylistContentPublic')->with($token->playlist);

        (new MakePlaylistSongsPublic($service))->handle(new NewPlaylistCollaboratorJoined(create_user(), $token));
    }
}
