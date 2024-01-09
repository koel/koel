<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Models\User;
use App\Services\Streamers\DirectStreamerInterface;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use Mockery;
use Tests\TestCase;

class SongPlayTest extends TestCase
{
    public function testPlay(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        $mockStreamer = $this->mock(DirectStreamerInterface::class);

        $mockStreamer->shouldReceive('setSong')->with(
            Mockery::on(static fn (Song $retrievedSong): bool => $retrievedSong->id === $song->id)
        )->once();

        $mockStreamer->shouldReceive('stream')->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();
    }
}
