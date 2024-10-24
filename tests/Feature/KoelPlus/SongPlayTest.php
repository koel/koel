<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Song;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_user;
use function Tests\test_path;

class SongPlayTest extends PlusTestCase
{
    #[Test]
    public function playPublicUnownedSong(): void
    {
        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken(create_user());

        /** @var Song $song */
        $song = Song::factory()->public()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        $this->mock(LocalStreamerAdapter::class)
            ->shouldReceive('stream')
            ->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();
    }

    #[Test]
    public function playPrivateOwnedSong(): void
    {
        /** @var Song $song */
        $song = Song::factory()->private()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($song->owner);

        $this->mock(LocalStreamerAdapter::class)
            ->shouldReceive('stream')
            ->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();
    }

    #[Test]
    public function cannotPlayPrivateUnownedSong(): void
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
