<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Album;
use App\Models\Song;
use App\Services\MediaMetadataService;
use Mockery;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class AlbumCoverTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    public function testNormalUserCanUploadCoverIfOwningAllSongsInAlbum(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();
        $album->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/albums/$album->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    public function testNormalUserCannotUploadCoverIfNotOwningAllSongsInAlbum(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();
        $album->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());
        $album->songs()->save(Song::factory()->create());

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->never();

        $this->putAs("api/albums/$album->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }

    public function testAdminCanUploadCoverEvenIfNotOwningAllSongsInAlbum(): void
    {
        $user = create_user();

        /** @var Album $album */
        $album = Album::factory()->create();
        $album->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeAlbumCover')
            ->once()
            ->with(Mockery::on(static fn (Album $target) => $target->is($album)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/albums/$album->id/cover", ['cover' => 'data:image/jpeg;base64,Rm9v'], create_admin())
            ->assertOk();
    }
}
