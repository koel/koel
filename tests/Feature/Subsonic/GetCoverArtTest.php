<?php

namespace Tests\Feature\Subsonic;

use App\Models\Album;
use App\Models\Artist;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

use function Tests\create_playlist;
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

        self::assertSame(File::get(image_storage_path($filename)), $response->streamedContent());
    }

    #[Test]
    public function answersNotModifiedWhenTheClientHasTheSameCover(): void
    {
        $user = create_user();
        $filename = $this->stageCover('sub_album_cover_etag_test.png');
        $album = Album::factory()->createOne(['cover' => $filename, 'user_id' => $user->id]);
        $url = "/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&id={$album->id}";

        $etag = $this->get($url)->assertOk()->headers->get('ETag');

        $response = $this->get($url, ['If-None-Match' => $etag])->assertNotModified();

        self::assertSame('', $response->streamedContent());
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
    public function returnsTheAlbumCoverForAnArtistWithoutAnImage(): void
    {
        $user = create_user();
        $filename = $this->stageCover('sub_artist_album_cover_test.png');

        $artist = Artist::factory()->createOne(['image' => '', 'user_id' => $user->id]);
        Album::factory()->for($artist)->createOne(['cover' => $filename, 'user_id' => $user->id]);

        $response = $this->get("/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&id={$artist->id}")->assertOk();

        self::assertSame(File::get(image_storage_path($filename)), $response->streamedContent());
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

    #[Test]
    public function returnsPlaylistCoverBytes(): void
    {
        $filename = $this->stageCover('sub_playlist_cover_test.png');
        $playlist = create_playlist(['cover' => $filename]);

        $response = $this->get(
            "/rest/getCoverArt.view?apiKey={$playlist->owner->subsonic_api_key}&id={$playlist->id}",
        )->assertOk();

        self::assertSame(File::get(image_storage_path($filename)), $response->streamedContent());
    }

    #[Test]
    public function refusesTheCoverOfSomeoneElsesPlaylist(): void
    {
        $playlist = create_playlist(['cover' => $this->stageCover('sub_other_playlist_cover_test.png')]);
        $user = create_user();

        $this->getJson(
            "/rest/getCoverArt.view?apiKey={$user->subsonic_api_key}&f=json&id={$playlist->id}",
        )->assertJsonPath('subsonic-response.error.code', 50);
    }

    private function stageCover(string $filename): string
    {
        $path = image_storage_path($filename);
        File::copy(test_path('fixtures/cover.png'), $path);

        $this->createdPaths[] = $path;

        return $filename;
    }
}
