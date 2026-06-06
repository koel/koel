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
        File::delete($this->createdPaths);

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
    public function albumWithoutCoverReturnsDefaultPlaceholder(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['cover' => '', 'user_id' => $user->id]);

        $response = $this->get(
            "/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&id={$album->id}",
        )->assertOk()->assertHeader('Content-Type', 'image/png');

        $base = $response->baseResponse;
        self::assertInstanceOf(BinaryFileResponse::class, $base);
        self::assertSame(resource_path('assets/img/covers/default.png'), $base->getFile()->getRealPath());
    }

    #[Test]
    public function albumWithMissingCoverFileReturnsDefaultPlaceholder(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['cover' => 'never-staged.png', 'user_id' => $user->id]);

        $response = $this->get(
            "/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&id={$album->id}",
        )->assertOk()->assertHeader('Content-Type', 'image/png');

        $base = $response->baseResponse;
        self::assertInstanceOf(BinaryFileResponse::class, $base);
        self::assertSame(resource_path('assets/img/covers/default.png'), $base->getFile()->getRealPath());
    }

    private function stageCover(string $filename): string
    {
        $path = image_storage_path($filename);
        File::copy(test_path('fixtures/cover.png'), $path);

        $this->createdPaths[] = $path;

        return $filename;
    }
}
