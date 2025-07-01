<?php

namespace Tests\Feature;

use App\Models\Artist;
use App\Services\ArtworkService;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_admin;

class ArtistImageTest extends TestCase
{
    private ArtworkService|MockInterface $artworkService;

    public function setUp(): void
    {
        parent::setUp();

        $this->artworkService = $this->mock(ArtworkService::class);
    }

    #[Test]
    public function update(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->artworkService
            ->expects('storeArtistImage')
            ->with(Mockery::on(static fn (Artist $target) => $target->is($artist)), 'data:image/jpeg;base64,Rm9v');

        $this->putAs(
            "api/artist/{$artist->public_id}/image",
            ['image' => 'data:image/jpeg;base64,Rm9v'],
            create_admin(),
        )->assertOk();
    }

    #[Test]
    public function updateNotAllowedForNormalUsers(): void
    {
        /** @var Artist $artist */
        $artist = Artist::factory()->create();

        $this->artworkService->shouldNotReceive('storeArtistImage');

        $this->putAs("api/artist/{$artist->public_id}/image", ['image' => 'data:image/jpeg;base64,Rm9v'])
            ->assertForbidden();
    }
}
