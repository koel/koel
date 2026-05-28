<?php

namespace Tests\Feature\Subsonic;

use App\Enums\FavoriteableType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Favorite;
use App\Models\Song;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function Tests\create_user;

class StarTest extends TestCase
{
    #[Test]
    public function favoritesSongAlbumAndArtistInOneCall(): void
    {
        $user = create_user();
        $song = Song::factory()->createOne(['owner_id' => $user->id]);
        $album = Album::factory()->createOne(['user_id' => $user->id]);
        $artist = Artist::factory()->createOne(['user_id' => $user->id]);

        $this
            ->getJson(
                "/rest/star.view?apiKey={$user->subsonic_api_key}"
                . "&f=json&id={$song->id}&albumId={$album->id}&artistId={$artist->id}",
            )
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertSame(3, Favorite::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function unstarRemovesFavorites(): void
    {
        $user = create_user();
        $album = Album::factory()->createOne(['user_id' => $user->id]);

        Favorite::factory()->createOne([
            'user_id' => $user->id,
            'favoriteable_type' => FavoriteableType::ALBUM->value,
            'favoriteable_id' => $album->id,
        ]);

        $this
            ->getJson("/rest/unstar.view?apiKey={$user->subsonic_api_key}&f=json&albumId={$album->id}")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');

        self::assertSame(0, Favorite::query()->where('user_id', $user->id)->count());
    }

    #[Test]
    public function preservesRequestOrderInFavoritesPosition(): void
    {
        $user = create_user();
        $songs = Song::factory()->count(3)->create(['owner_id' => $user->id]);
        // request order: songs[2], songs[0], songs[1]
        $ordered = [$songs[2]->id, $songs[0]->id, $songs[1]->id];
        $idParams = implode('&', array_map(static fn (string $id) => "id={$id}", $ordered));

        $this->getJson("/rest/star.view?apiKey={$user->subsonic_api_key}&f=json&{$idParams}")->assertOk();

        $favoriteIdsByPosition = Favorite::query()
            ->where('user_id', $user->id)
            ->orderBy('position')
            ->pluck('favoriteable_id')
            ->all();

        self::assertSame($ordered, $favoriteIdsByPosition);
    }

    #[Test]
    public function emptyCallIsNoOp(): void
    {
        $user = create_user();

        $this
            ->getJson("/rest/star.view?apiKey={$user->subsonic_api_key}&f=json")
            ->assertOk()
            ->assertJsonPath('subsonic-response.status', 'ok');
    }
}
