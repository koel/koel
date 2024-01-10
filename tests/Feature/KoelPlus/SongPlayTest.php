<?php

namespace Tests\Feature\KoelPlus;

use App\Facades\License;
use App\Models\Song;
use App\Models\User;
use App\Services\Streamers\DirectStreamerInterface;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use Mockery;
use Tests\TestCase;

class SongPlayTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        License::fakePlusLicense();
    }

    public function testPlayPublicUnownedSong(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

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

        /** @var User $owner */
        $owner = User::factory()->create();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($owner);

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertForbidden();
    }
}
