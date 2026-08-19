<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Auth\TokenManager;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\ProgressiveTranscodingStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Values\CompositeToken;
use App\Values\RequestedStreamingConfig;
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
        $song = Song::factory()->createOne([
            'path' => test_path('songs/blank.mp3'),
        ]);

        $this->mock(LocalStreamerAdapter::class)->expects('stream');

        $this->get("play/{$song->id}?t=$token->audioToken&progressive=1")->assertOk();
    }

    #[Test]
    public function transcodingFlacNeverStreamsProgressively(): void
    {
        config(['koel.streaming.transcode_flac' => true]);
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne([
            'path' => '/tmp/blank.flac',
            'mime_type' => 'audio/flac',
        ]);

        $this
            ->mock(TranscodingStreamerAdapter::class)
            ->expects('stream')
            ->withArgs(
                static fn (Song $streamedSong, RequestedStreamingConfig $config): bool => (
                    $streamedSong->is($song) && $config->progressive
                ),
            );

        $this->get("play/{$song->id}?t=$token->audioToken&progressive=1")->assertOk();

        config(['koel.streaming.transcode_flac' => false]);
    }

    #[Test]
    public function forceTranscodingIgnoresProgressiveRequests(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne(['path' => '/var/songs/blank.mp3']);

        $this
            ->mock(TranscodingStreamerAdapter::class)
            ->expects('stream')
            ->withArgs(
                static fn (Song $streamedSong, RequestedStreamingConfig $config): bool => (
                    $streamedSong->is($song) && !$config->progressive
                ),
            );

        $this->get("play/{$song->id}/1?t=$token->audioToken&progressive=1")->assertOk();
    }

    #[Test]
    public function legacyMobileBitRateSegmentRemainsSupported(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne(['path' => '/var/songs/blank.mp3']);

        $this
            ->mock(TranscodingStreamerAdapter::class)
            ->expects('stream')
            ->withArgs(
                static fn (Song $streamedSong, RequestedStreamingConfig $config): bool => (
                    $streamedSong->is($song)
                    && $config->transcode
                    && $config->bitRate === 128
                    && !$config->progressive
                ),
            );

        $this->get("play/{$song->id}/1/128?t=$token->audioToken&progressive=1")->assertOk();
    }

    #[Test]
    public function legacyMobileBitRateSegmentRejectsUnsupportedValues(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne(['path' => '/var/songs/blank.mp3']);

        $this->get("play/{$song->id}/1/160?t=$token->audioToken")->assertNotFound();
    }

    #[Test]
    public function progressiveCompatibilityTranscodingPassesRequestedStartTime(): void
    {
        config([
            'koel.streaming.progressive' => true,
            'koel.streaming.transcode_required_mime_types' => ['audio/aiff'],
        ]);
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne([
            'path' => '/var/songs/blank.aiff',
            'mime_type' => 'audio/aiff',
        ]);

        $this
            ->mock(ProgressiveTranscodingStreamerAdapter::class)
            ->expects('stream')
            ->withArgs(
                static fn (Song $streamedSong, RequestedStreamingConfig $config): bool => (
                    $streamedSong->is($song)
                    && $config->progressive
                    && $config->startTime === 87.25
                ),
            );

        $this->get("play/{$song->id}?t=$token->audioToken&progressive=1&time=87.25")->assertOk();
    }

    #[Test]
    public function invalidProgressiveAndStartTimeQueriesAreRejected(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne();

        $this
            ->getJson("play/{$song->id}?t=$token->audioToken&progressive=sometimes&time=-1")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['progressive', 'time']);
    }
}
