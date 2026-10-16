<?php

namespace Tests\Feature;

use App\Http\Resources\ArtistResource;
use App\Models\Artist;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class ArtistSearchTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('scout.driver', 'collection');
    }

    protected function tearDown(): void
    {
        config()->set('scout.driver', null);

        parent::tearDown();
    }

    #[Test]
    public function search(): void
    {
        $user = create_user();
        $match = Artist::factory()->for($user)->createOne(['name' => 'Foo Bar']);
        Artist::factory()->for($user)->createOne(['name' => 'Something Else']);

        $this
            ->getAs('api/search/artists?q=foo', $user)
            ->assertJsonStructure([0 => ArtistResource::JSON_STRUCTURE])
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $match->id);
    }
}
