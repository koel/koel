<?php

namespace Tests\Feature;

use App\Http\Resources\AlbumResource;
use App\Models\Album;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class AlbumSearchTest extends TestCase
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
        $match = Album::factory()->for($user)->createOne(['name' => 'Foo Bar']);
        Album::factory()->for($user)->createOne(['name' => 'Something Else']);

        $this
            ->getAs('api/search/albums?q=foo', $user)
            ->assertJsonStructure([0 => AlbumResource::JSON_STRUCTURE])
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $match->id);
    }
}
