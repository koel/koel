<?php

namespace Tests\Feature\Commands;

use App\Models\Album;
use App\Services\AlbumService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateSmallCoversCommandTest extends TestCase
{
    #[Test]
    public function generateSmallCoversForAlbumsWithoutOne(): void
    {
        $albumWithoutSmallCover = Album::factory()->createOne(['small_cover' => null]);
        Album::factory()->createOne(['small_cover' => 'small.webp']);
        Album::factory()->createOne(['cover' => '', 'small_cover' => null]);

        $this
            ->mock(AlbumService::class)
            ->expects('generateAlbumSmallCover')
            ->once()
            ->with(Mockery::on(static fn (Album $album) => $album->is($albumWithoutSmallCover)));

        $this->artisan('koel:generate-small-covers')->assertSuccessful();
    }
}
