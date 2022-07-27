<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Models\Album;
use App\Models\User;
use App\Services\MediaMetadataService;
use Mockery;

class AlbumCoverTest extends TestCase
{
    private $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    public function testUpdate(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        /** @var Album $album */
        $album = Album::factory()->create(['id' => 9999]);

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static function (Album $album): bool {
                return $album->id === 9999;
            }), 'Foo', 'jpeg');

        $response = $this->putAs('api/album/' . $album->id . '/cover', [
            'cover' => 'data:image/jpeg;base64,Rm9v',
        ], User::factory()->admin()->create());

        $response->assertStatus(200);
    }

    public function testUpdateNotAllowedForNormalUsers(): void
    {
        /** @var Album $album */
        $album = Album::factory()->create();

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->never();

        $this->putAs('api/album/' . $album->id . '/cover', [
            'cover' => 'data:image/jpeg;base64,Rm9v',
        ], User::factory()->create())
            ->assertStatus(403);
    }
}
