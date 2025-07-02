<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class SongPlayTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Start output buffering to prevent binary data from being sent to the console during tests
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();

        parent::tearDown();
    }

    #[Test]
    public function play(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => test_path('songs/blank.mp3'),
        ]);

        $this->mock(LocalStreamerAdapter::class)
            ->expects('stream');

        $this->get("play/{$song->id}?t=$token->audioToken")
            ->assertOk();
    }

    #[Test]
    public function transcoding(): void
    {
        config(['koel.streaming.transcode_flac' => true]);
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create([
            'path' => '/tmp/blank.flac',
            'mime_type' => 'audio/flac',
        ]);

        $this->mock(TranscodingStreamerAdapter::class)
            ->expects('stream');

        $this->get("play/{$song->id}?t=$token->audioToken")
            ->assertOk();

        config(['koel.streaming.transcode_flac' => false]);
    }

    #[Test]
    public function forceTranscoding(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create(['path' => '/var/songs/blank.mp3']);

        $this->mock(TranscodingStreamerAdapter::class)
            ->expects('stream');

        $this->get("play/{$song->id}/1?t=$token->audioToken")
            ->assertOk();
    }
}
