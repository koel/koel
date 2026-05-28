<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
use App\Services\Streamer\Adapters\LocalStreamerAdapter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class DownloadTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();

        parent::tearDown();
    }

    #[Test]
    public function downloadsAudioBytes(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne([
            'path' => test_path('songs/blank.mp3'),
            'owner_id' => $user->id,
        ]);

        $this->mock(LocalStreamerAdapter::class)->expects('stream');

        $this->get("/rest/download.view?id={$song->id}&apiKey={$user->subsonic_api_key}")->assertOk();
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/download.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }
}
