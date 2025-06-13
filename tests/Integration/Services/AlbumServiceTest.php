<?php

namespace Tests\Integration\Services;

use App\Exceptions\AlbumNameConflictException;
use App\Models\Album;
use App\Services\AlbumService;
use App\Values\AlbumUpdateData;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class AlbumServiceTest extends TestCase
{
    private AlbumService $albumService;

    public function setUp(): void
    {
        parent::setUp();

        $this->albumService = app(AlbumService::class);
    }

    #[Test]
    public function updateAlbum(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create([
            'name' => 'Old Album Name',
            'year' => 2020,
        ]);

        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023);

        $updatedAlbum = $this->albumService->updateAlbum($album, $data);

        $this->assertEquals('New Album Name', $updatedAlbum->name);
        $this->assertEquals(2023, $updatedAlbum->year);
    }

    #[Test]
    public function rejectUpdatingUnknownAlbum(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create(['name' => Album::UNKNOWN_NAME]);
        $data = AlbumUpdateData::make(name: 'New Album Name', year: 2023);

        $this->expectException(InvalidArgumentException::class);

        $this->albumService->updateAlbum($album, $data);
    }

    #[Test]
    public function rejectUpdatingIfArtistAlreadyHasAnAlbumWithTheSameName(): void
    {
        /** @var Album $existingAlbum */
        $existingAlbum = Album::factory()->create(['name' => 'Existing Album Name']);

        /** @var Album $album */
        $album = Album::factory()->for($existingAlbum->artist)->create(['name' => 'Old Album Name']);
        $data = AlbumUpdateData::make(name: 'Existing Album Name', year: 2023);

        $this->expectException(AlbumNameConflictException::class);

        $this->albumService->updateAlbum($album, $data);
    }
}
