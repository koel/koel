<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

use function Tests\create_user;
use function Tests\test_path;

class GetCoverArtTest extends TestCase
{
    /** @var list<string> */
    private array $createdPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->createdPaths as $path) {
            File::delete($path);
        }

        parent::tearDown();
    }

    #[Test]
    public function returnsAlbumCoverBytes(): void
    {
        $user = create_user();
        $filename = $this->stageCover('sub_album_cover_test.png');

        $album = Album::factory()->createOne(['cover' => $filename, 'user_id' => $user->id]);

        $response = $this->get(
            "/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&id={$album->id}",
        )->assertOk()->assertHeader('Content-Type', 'image/png');

        $base = $response->baseResponse;
        self::assertInstanceOf(BinaryFileResponse::class, $base);
        self::assertSame(image_storage_path($filename), $base->getFile()->getRealPath());
    }

    #[Test]
    public function returnsArtistImageBytes(): void
    {
        $user = create_user();
        $filename = $this->stageCover('sub_artist_image_test.png');

        $artist = Artist::factory()->createOne(['image' => $filename, 'user_id' => $user->id]);

        $this->get("/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&id={$artist->id}")->assertOk();
    }

    #[Test]
    public function unknownIdReturnsCode70(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&f=json&id=does-not-exist")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    #[Test]
    public function albumWithoutCoverReturnsCode70(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['cover' => null, 'user_id' => $user->id]);

        $this
            ->getJson("/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&f=json&id={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.error.code', 70);
    }

    private function stageCover(string $filename): string
    {
        $path = image_storage_path($filename);
        File::copy(test_path('fixtures/cover.png'), $path);

        $this->createdPaths[] = $path;

        return $filename;
    }
}
