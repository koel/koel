<?php

namespace Tests\Feature\KoelPlus;

use App\Events\LibraryChanged;
use App\Models\Artist;
use App\Models\Song;
use App\Services\MediaMetadataService;
use Mockery;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class ArtistImageTest extends PlusTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    public function testNormalUserCanUploadImageIfOwningAllSongsInArtist(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $artist->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->once()
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), 'Foo', 'jpeg');

        $this->putAs("api/artist/$artist->id/image", ['image' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    public function testNormalUserCannotUploadImageIfNotOwningAllSongsInArtist(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $artist->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());
        $artist->songs()->save(Song::factory()->create());

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->never();

        $this->putAs("api/artist/$artist->id/image", ['image' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }

    public function testAdminCanUploadImageEvenIfNotOwningAllSongsInArtist(): void
    {
        $this->expectsEvents(LibraryChanged::class);

        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $artist->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->once()
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), 'Foo', 'jpeg');

        $this->putAs("api/artist/$artist->id/image", ['image' => 'data:image/jpeg;base64,Rm9v'], create_admin())
            ->assertOk();
    }
}
