<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Services\Streamers\DirectStreamerInterface;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use Mockery;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class SongPlayTest extends PlusTestCase
{
    public function testPlayPublicUnownedSong(): void
    {
        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken(create_user());

        /** @var Song $song */
        $song = Song::factory()->public()->create([
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

    public function testPlayPrivateOwnedSong(): void
    {
        /** @var Song $song */
        $song = Song::factory()->private()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($song->owner);

        $mockStreamer = $this->mock(DirectStreamerInterface::class);

        $mockStreamer->shouldReceive('setSong')->with(
            Mockery::on(static fn (Song $retrievedSong): bool => $retrievedSong->id === $song->id)
        )->once();

        $mockStreamer->shouldReceive('stream')->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();
    }

    public function testCannotPlayPrivateUnownedSong(): void
    {
        /** @var Song $song */
        $song = Song::factory()->private()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken(create_user());

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertForbidden();
    }
}
