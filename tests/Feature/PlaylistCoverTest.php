<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_playlist;
use function Tests\create_user;

class PlaylistCoverTest extends TestCase
{
    #[Test]
    public function deleteCover(): void
    {
        File::put(image_storage_path('foo.webp'), 'fake-content');
        $playlist = create_playlist(['cover' => 'foo.webp']);

        $this->deleteAs("api/playlists/{$playlist->id}/cover", [], $playlist->owner)
            ->assertNoContent();

        self::assertNull($playlist->refresh()->cover);
        self::assertFileDoesNotExist(image_storage_path('foo.webp'));
    }

    #[Test]
    public function nonOwnerCannotDeleteCover(): void
    {
        File::put(image_storage_path('foo.webp'), 'fake-content');
        $playlist = create_playlist(['cover' => 'foo.webp']);

        $this->deleteAs("api/playlists/{$playlist->id}/cover", [], create_user())
            ->assertForbidden();

        self::assertSame(image_storage_url('foo.webp'), $playlist->refresh()->cover);
        self::assertFileExists(image_storage_path('foo.webp'));
    }
}
