<?php

namespace Tests\Feature;

use App\Helpers\Ulid;
use App\Models\Album;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class AlbumCoverTest extends TestCase
{
    #[Test]
    public function destroy(): void
    {
        $file = Ulid::generate() . '.jpg';
        File::put(image_storage_path($file), 'foo');

        /** @var Album $album */
        $album = Album::factory()->create([
            'cover' => $file,
        ]);

        $this->deleteAs("api/albums/{$album->id}/cover", [], create_admin())
            ->assertNoContent();

        self::assertNull($album->refresh()->cover);
        self::assertFileDoesNotExist(image_storage_path($file));
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
