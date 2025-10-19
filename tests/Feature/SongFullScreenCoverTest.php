<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Song;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SongFullScreenCoverTest extends TestCase
{
    #[Test]
    public function songReturnsFullScreenCoverUrlIfFileExists(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create([
            'cover' => 'foo.jpg',
        ]);

        File::put(image_storage_path('foo_fullscreen.jpg'), 'fake');

        /** @var Song $song */
        $song = Song::factory()->for($album)->create();

        $this->getAs("api/songs/{$song->id}")
            ->assertJson([
                'full_screen_cover' => image_storage_url('foo_fullscreen.jpg'),
            ]);
    }

    #[Test]
    public function songReturnsNullIfFullScreenCoverIsMissing(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create([
            'cover' => 'bar.jpg',
        ]);

        /** @var Song $song */
        $song = Song::factory()->for($album)->create();

        $this->getAs("api/songs/{$song->id}")
            ->assertJson([
                'full_screen_cover' => null,
            ]);
    }
}
