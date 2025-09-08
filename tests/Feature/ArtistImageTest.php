<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Models\Artist;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class ArtistImageTest extends TestCase
{
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
