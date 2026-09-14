<?php

namespace Tests\Feature;

use App\Models\Song;
use App\Services\Auth\TokenManager;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use App\Services\Streamer\Adapters\TranscodingStreamerAdapter;
use App\Values\CompositeToken;
use Illuminate\Http\Response;
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

        $this->get("play/{$song->id}?t=$token->audioToken")->assertOk();
    }

    #[Test]
    public function serveTheExactRangeAsked(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $path = test_path('songs/blank.mp3');
        $song = Song::factory()->createOne(['path' => $path]);
        $size = filesize($path);

        $response = $this->get("play/{$song->id}?t=$token->audioToken", ['Range' => 'bytes=0-99']);

        $response
            ->assertStatus(Response::HTTP_PARTIAL_CONTENT)
            ->assertHeader('content-length', '100')
            ->assertHeader('content-range', "bytes 0-99/$size");

        self::assertSame(100, strlen($response->streamedContent()));
    }

    #[Test]
    public function answerSafarisTwoByteProbe(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $path = test_path('songs/blank.mp3');
        $song = Song::factory()->createOne(['path' => $path]);

        $this
            ->get("play/{$song->id}?t=$token->audioToken", ['Range' => 'bytes=0-1'])
            ->assertStatus(Response::HTTP_PARTIAL_CONTENT)
            ->assertHeader('content-length', '2')
            ->assertHeader('content-range', 'bytes 0-1/' . filesize($path));
    }

    #[Test]
    public function seekToTheBytesAskedFor(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $path = test_path('songs/blank.mp3');
        $song = Song::factory()->createOne(['path' => $path]);
        $offset = (int) (filesize($path) / 2);

        $response = $this->get("play/{$song->id}?t=$token->audioToken", [
            'Range' => "bytes=$offset-" . ($offset + 63),
        ]);

        $response->assertStatus(Response::HTTP_PARTIAL_CONTENT);

        self::assertSame(file_get_contents($path, offset: $offset, length: 64), $response->streamedContent());
    }

    #[Test]
    public function nameTheFileWithoutAskingBrowsersToSaveIt(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne(['path' => test_path('songs/blank.mp3')]);

        $this->get("play/{$song->id}?t=$token->audioToken")->assertOk()->assertHeader(
            'content-disposition',
            'inline; filename=blank.mp3',
        );
    }

    #[Test]
    public function serveTheWholeFileWhenNoRangeIsAsked(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $path = test_path('songs/blank.mp3');
        $song = Song::factory()->createOne(['path' => $path]);

        $this
            ->get("play/{$song->id}?t=$token->audioToken")
            ->assertOk()
            ->assertHeader('content-length', (string) filesize($path))
            ->assertHeader('accept-ranges', 'bytes');
    }

    #[Test]
    public function transcoding(): void
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
            ->andReturn(response()->file(test_path('songs/blank.mp3')));

        $this->get("play/{$song->id}?t=$token->audioToken")->assertOk();

        config(['koel.streaming.transcode_flac' => false]);
    }

    #[Test]
    public function forceTranscoding(): void
    {
        $user = create_user();

        /** @var CompositeToken $token */
        $token = app(TokenManager::class)->createCompositeToken($user);
        $song = Song::factory()->createOne(['path' => '/var/songs/blank.mp3']);

        $this
            ->mock(TranscodingStreamerAdapter::class)
            ->expects('stream')
            ->andReturn(response()->file(test_path('songs/blank.mp3')));

        $this->get("play/{$song->id}/1?t=$token->audioToken")->assertOk();
    }
}
