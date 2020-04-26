<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Models\Album;
use App\Models\User;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;

class AlbumCoverTest extends TestCase
{
    /** @var MockInterface|MediaMetadataService */
    private $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mediaMetadataService = $this->mockIocDependency(MediaMetadataService::class);
    }

    public function testUpdate(): void
    {
        $this->expectsEvents(LibraryChanged::class);
        factory(Album::class)->create(['id' => 9999]);

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static function (Album $album): bool {
                return $album->id === 9999;
            }), 'Foo', 'jpeg');

        $this->putAsUser('api/album/9999/cover', [
            'cover' => 'data:image/jpeg;base64,Rm9v'
        ], factory(User::class, 'admin')->create());
    }
}
