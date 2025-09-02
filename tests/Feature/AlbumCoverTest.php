<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Models\Album;
use App\Services\ArtworkService;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\create_user;
use function Tests\minimal_base64_encoded_image;

class AlbumCoverTest extends TestCase
{
    private ArtworkService|MockInterface $artworkService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artworkService = $this->mock(ArtworkService::class);
    }

    #[Test]
    public function update(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->artworkService
            ->expects('storeAlbumCover')
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), minimal_base64_encoded_image());

        $this->putAs("api/album/{$album->id}/cover", ['cover' => minimal_base64_encoded_image()], create_admin())
            ->assertOk();
    }

    #[Test]
    public function updateNotAllowedForNormalUsers(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->artworkService->shouldNotReceive('storeAlbumCover');

        $this->putAs("api/album/{$album->id}/cover", ['cover' => minimal_base64_encoded_image()], create_user())
            ->assertForbidden();
    }

    #[Test]
    public function destroy(): void
    {
        $file = Ulid::generate() . '.jpg';
        File::put(album_cover_path($file), 'foo');

        /** @var Album $album */
        $album = Album::factory()->create([
            'cover' => $file,
        ]);

        $this->deleteAs("api/albums/{$album->id}/cover", [], create_admin())
            ->assertNoContent();

        self::assertNull($album->refresh()->cover);
        self::assertFileDoesNotExist(album_cover_path($file));
    }

    #[Test]
    public function destroyNotAllowedForNormalUser(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->deleteAs("api/albums/{$album->id}/cover")
            ->assertForbidden();

        self::assertNotNull($album->refresh()->cover);
    }
}
