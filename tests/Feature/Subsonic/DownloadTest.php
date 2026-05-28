<?php

namespace Tests\Feature\Subsonic;

use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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
    public function servesAsAttachment(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne([
            'path' => test_path('songs/blank.mp3'),
            'owner_id' => $user->id,
        ]);

        $response = $this->get("/rest/download.view?id={$song->id}&apiKey={$user->subsonic_api_key}")->assertOk();

        $base = $response->baseResponse;
        self::assertInstanceOf(BinaryFileResponse::class, $base);
        self::assertStringContainsString('attachment', (string) $base->headers->get('Content-Disposition'));
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
