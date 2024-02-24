<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Services\TokenManager;
use App\Values\CompositeToken;
use Illuminate\Support\Facades\File;
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

        $this->mock(LocalStreamerAdapter::class)
            ->shouldReceive('stream')
            ->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();
    }

    public function testTranscoding(): void
    {
        config(['koel.streaming.transcode_flac' => true]);
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create(['path' => test_path('songs/blank.mp3')]);

        File::partialMock()
            ->shouldReceive('mimeType')
            ->with($song->path)
            ->andReturn('audio/flac');

        $this->mock(TranscodingStreamerAdapter::class)
            ->shouldReceive('stream')
            ->once();

        $this->get("play/$song->id?t=$token->audioToken")
            ->assertOk();

        config(['koel.streaming.transcode_flac' => false]);
    }

    public function testForceTranscoding(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);

        /** @var Song $song */
        $song = Song::factory()->create(['path' => test_path('songs/blank.mp3')]);

        $this->mock(TranscodingStreamerAdapter::class)
            ->shouldReceive('stream')
            ->once();

        $this->get("play/$song->id/1/128?t=$token->audioToken")
            ->assertOk();
    }
}
