<?php

namespace Tests\Feature\Subsonic;

use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Favorite;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class GetAlbumList2Test extends TestCase
{
    #[Test]
    public function newestReturnsRecentAlbums(): void
    {
        $user = create_user();

        Album::factory()->createMany([
            ['name' => 'Older', 'user_id' => $user->id, 'created_at' => now()->subDays(10)],
            ['name' => 'Newer', 'user_id' => $user->id, 'created_at' => now()],
        ]);

        $response = $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=newest&size=2")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        $names = array_column($response->json('subsonic-response.albumList2.album'), 'name');
        self::assertSame(['Newer', 'Older'], $names);
    }

    #[Test]
    public function alphabeticalByNameRespectsOffsetAndSize(): void
    {
        $user = create_user();

        foreach (['Alpha', 'Bravo', 'Charlie', 'Delta'] as $name) {
            Album::factory()->createOne(['name' => $name, 'user_id' => $user->id]);
        }

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}"
            . '&f=json&type=alphabeticalByName&size=2&offset=1',
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album'), 'name');
        self::assertSame(['Bravo', 'Charlie'], $names);
    }

    #[Test]
    public function randomReturnsRequestedNumber(): void
    {
        $user = create_user();

        Album::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=random&size=3",
        )->assertOk();

        self::assertCount(3, $response->json('subsonic-response.albumList2.album'));
    }

    #[Test]
    public function starredReturnsOnlyFavoritedAlbums(): void
    {
        $user = create_user();

        $favorited = Album::factory()->createOne(['name' => 'Liked', 'user_id' => $user->id]);
        Album::factory()->createOne(['name' => 'NotLiked', 'user_id' => $user->id]);

        Favorite::factory()->createOne([
            'user_id' => $user->id,
            'favoriteable_type' => FavoriteableType::ALBUM->value,
            'favoriteable_id' => $favorited->id,
        ]);

        $response = $this->getJson(
            "/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=starred",
        )->assertOk();

        $names = array_column($response->json('subsonic-response.albumList2.album') ?? [], 'name');
        self::assertSame(['Liked'], $names);
    }

    #[Test]
    public function frequentReturnsAtMostRequestedSize(): void
    {
        $user = create_user();

        Album::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=frequent&size=2")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertLessThanOrEqual(2, count($response->json('subsonic-response.albumList2.album') ?? []));
    }

    #[Test]
    public function unsupportedTypeReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json&type=byYear")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }

    #[Test]
    public function missingTypeReturnsCode10(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/getAlbumList2.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'failed')
            ->assertJsonPath('subsonic-response.error.code', 10);
    }
}
