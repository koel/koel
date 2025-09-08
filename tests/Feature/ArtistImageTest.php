<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Models\Artist;
use App\Services\ArtworkService;
use Illuminate\Support\Facades\File;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;
use function Tests\minimal_base64_encoded_image;

class ArtistImageTest extends TestCase
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
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->artworkService
            ->expects('storeArtistImage')
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), minimal_base64_encoded_image());

        $this->putAs(
            "api/artist/{$artist->id}/image",
            ['image' => minimal_base64_encoded_image()],
            create_admin(),
        )->assertOk();
    }

    #[Test]
    public function updateNotAllowedForNormalUsers(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->artworkService->shouldNotReceive('storeArtistImage');

        $this->putAs("api/artist/{$artist->id}/image", ['image' => minimal_base64_encoded_image()])
            ->assertForbidden();
    }

    #[Test]
    public function destroy(): void
    {
        $file = Ulid::generate() . '.jpg';
        File::put(image_storage_path($file), 'foo');

        /** @var Artist $artist */
        $artist = Artist::factory()->create([
            'image' => $file,
        ]);

        $this->deleteAs("api/artists/{$artist->id}/image", [], create_admin())
            ->assertNoContent();

        self::assertNull($artist->refresh()->image);
        self::assertFileDoesNotExist(image_storage_path($file));
    }

    #[Test]
    public function destroyNotAllowedForNormalUser(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->deleteAs("api/artists/{$artist->id}/image")
            ->assertForbidden();

        self::assertNotNull($artist->refresh()->image);
    }
}
