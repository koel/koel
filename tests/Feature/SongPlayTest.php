<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Streamers\LocalStreamer;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use Mockery;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class SongPlayTest extends TestCase
{
    public function testPlay(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        $mockStreamer = $this->mock(LocalStreamer::class);

        $mockStreamer->shouldReceive('setSong')->with(
            Mockery::on(static fn (Song $retrievedSong): bool => $retrievedSong->id === $song->id)
        )->once();

        $mockStreamer->shouldReceive('stream')->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();
    }
}
