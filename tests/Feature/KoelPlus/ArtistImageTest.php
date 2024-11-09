<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Artist;
use App\Models\Song;
use App\Services\MediaMetadataService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\PlusTestCase;

use function Tests\create_admin;
use function Tests\create_user;

class ArtistImageTest extends PlusTestCase
{
    private MockInterface|MediaMetadataService $mediaMetadataService;

    public function setUp(): void
    {
        parent::setUp();

        $this->mediaMetadataService = self::mock(MediaMetadataService::class);
    }

    #[Test]
    public function normalUserCanUploadImageIfOwningAllSongsInArtist(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $artist->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->once()
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/artists/{$artist->id}/image", ['image' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    #[Test]
    public function normalUserCannotUploadImageIfNotOwningAllSongsInArtist(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $artist->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());
        $artist->songs()->save(Song::factory()->create());

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->never();

        $this->putAs("api/artists/{$artist->id}/image", ['image' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }

    #[Test]
    public function adminCanUploadImageEvenIfNotOwningAllSongsInArtist(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();
        $artist->songs()->saveMany(Song::factory()->for($user, 'owner')->count(3)->create());

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->once()
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs(
            "api/artists/{$artist->id}/image",
            ['image' => 'data:image/jpeg;base64,Rm9v'],
            create_admin()
        )
            ->assertOk();
    }
}
