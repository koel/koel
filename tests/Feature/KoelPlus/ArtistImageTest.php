<?php

namespace Tests\Feature\KoelPlus;

use App\Models\Artist;
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

        $this->mediaMetadataService = $this->mock(MediaMetadataService::class);
    }

    #[Test]
    public function ownerCanUploadImage(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->for($user)->create();

        self::assertTrue($artist->belongsToUser($user));

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->once()
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs("api/artists/{$artist->public_id}/image", ['image' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertOk();
    }

    #[Test]
    public function nonOwnerCannotUploadImage(): void
    {
        $user = create_user();

        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        self::assertFalse($artist->belongsToUser($user));

        $this->mediaMetadataService
            ->shouldReceive('writeArtistImage')
            ->never();

        $this->putAs("api/artists/{$artist->public_id}/image", ['image' => 'data:image/jpeg;base64,Rm9v'], $user)
            ->assertForbidden();
    }

    #[Test]
    public function evenAdminCannotUploadImageIfNotOwning(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->putAs(
            "api/artists/{$artist->public_id}/image",
            ['image' => 'data:image/jpeg;base64,Rm9v'],
            create_admin(),
        )->assertForbidden();
    }
}
