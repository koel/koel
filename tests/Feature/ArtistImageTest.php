<?php

namespace Tests\Feature;

use App\Events\LibraryChanged;
use App\Models\Artist;
use App\Models\User;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;

class ArtistImageTest extends TestCase
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
        factory(Artist::class)->create(['id' => 9999]);

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->once()
            ->with(Mockery::on(static function (Artist $artist): bool {
               return $artist->id === 9999;
            }), 'Foo', 'jpeg');

        $this->putAsUser('api/artist/9999/image', [
            'image' => 'data:image/jpeg;base64,Rm9v'
        ], factory(User::class, 'admin')->create());
    }
}
